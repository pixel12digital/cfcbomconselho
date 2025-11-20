# Correções na Aba Matrícula - CFC Bom Conselho

## Resumo Executivo

Este documento detalha todas as correções aplicadas na aba Matrícula do módulo de Alunos, conforme solicitado pelo usuário.

**Data:** 20/11/2025  
**Arquivos Modificados:** 3  
**Correções Aplicadas:** 5 principais

---

## 1. RENACH da Matrícula - Salvar e Exibir Corretamente

### Problema Identificado
- RENACH preenchido na aba Matrícula não era salvo no banco de dados
- Ao reabrir edição, campo aparecia vazio
- No modal Detalhes, RENACH aparecia como "Não informado"

### Correções Aplicadas

#### 1.1. Frontend - Inclusão do RENACH no Payload
**Arquivo:** `admin/pages/alunos.php` (linha ~7163)

**Antes:**
```javascript
const dadosMatricula = {
    aluno_id: parseInt(alunoIdHidden.value),
    categoria_cnh: categoriaCnh,
    tipo_servico: tipoServico,
    data_inicio: dataMatricula,
    // ... outros campos
    // RENACH não estava sendo enviado
};
```

**Depois:**
```javascript
const dadosMatricula = {
    aluno_id: parseInt(alunoIdHidden.value),
    categoria_cnh: categoriaCnh,
    tipo_servico: tipoServico,
    data_inicio: dataMatricula,
    // ... outros campos
    renach: renach || null,  // ✅ RENACH incluído
    processo_numero: formData.get('processo_numero') || null,
    processo_numero_detran: formData.get('processo_numero_detran') || null,
    processo_situacao: formData.get('processo_situacao') || null
};
```

#### 1.2. Backend - Salvar RENACH na Tabela matriculas
**Arquivo:** `admin/api/matriculas.php` (linha ~144)

**Antes:**
```php
$matriculaId = $db->insert('matriculas', [
    'aluno_id' => $input['aluno_id'],
    'categoria_cnh' => $input['categoria_cnh'],
    // ... outros campos
    // RENACH não estava sendo salvo
]);
```

**Depois:**
```php
$matriculaId = $db->insert('matriculas', [
    'aluno_id' => $input['aluno_id'],
    'categoria_cnh' => $input['categoria_cnh'],
    // ... outros campos
    'renach' => $input['renach'] ?? null,  // ✅ RENACH incluído
    'processo_numero' => $input['processo_numero'] ?? null,
    'processo_numero_detran' => $input['processo_numero_detran'] ?? null,
    'processo_situacao' => $input['processo_situacao'] ?? null
]);
```

#### 1.3. Frontend - Preencher RENACH ao Carregar Matrícula
**Arquivo:** `admin/pages/alunos.php` (linha ~7772)

**Adicionado:**
```javascript
// Preencher RENACH da matrícula
if (matricula.renach) {
    const renachField = document.getElementById('renach');
    if (renachField) {
        renachField.value = matricula.renach;
        logModalAluno('✅ RENACH da matrícula preenchido:', matricula.renach);
    }
}
```

#### 1.4. Backend - Incluir RENACH da Matrícula na API de Alunos
**Arquivo:** `admin/api/alunos.php` (linha ~403)

**Adicionado:**
```php
// Buscar matrícula ativa para incluir RENACH da matrícula
$matriculaAtiva = $db->fetch("
    SELECT renach, processo_numero, processo_numero_detran, processo_situacao, status, data_fim
    FROM matriculas 
    WHERE aluno_id = ? AND status = 'ativa'
    ORDER BY data_inicio DESC
    LIMIT 1
", [$id]);

if ($matriculaAtiva) {
    // Se houver RENACH na matrícula, usar ele
    if (!empty($matriculaAtiva['renach'])) {
        $aluno['renach_matricula'] = $matriculaAtiva['renach'];
    }
    // Incluir outros dados da matrícula
    $aluno['numero_processo'] = $matriculaAtiva['processo_numero'] ?? null;
    $aluno['detran_numero'] = $matriculaAtiva['processo_numero_detran'] ?? null;
    $aluno['processo_situacao'] = $matriculaAtiva['processo_situacao'] ?? null;
    $aluno['status_matricula'] = $matriculaAtiva['status'] ?? null;
    $aluno['data_conclusao'] = $matriculaAtiva['data_fim'] ?? null;
}
```

#### 1.5. Frontend - Modal Detalhes Usa RENACH da Matrícula
**Arquivo:** `admin/pages/alunos.php` (linha ~5051)

**Antes:**
```javascript
<p><strong>RENACH:</strong> ${aluno.renach || 'Não informado'}</p>
```

**Depois:**
```javascript
<p><strong>RENACH:</strong> ${(aluno.renach_matricula || aluno.renach) || 'Não informado'}</p>
```

**Prioridade:** RENACH da matrícula tem prioridade sobre RENACH do aluno.

---

## 2. Erro "Já Existe Matrícula Ativa" ao Editar

### Problema Identificado
- Ao editar uma matrícula já criada, o sistema retornava erro:
  - "Já existe uma matrícula ativa para esta categoria e tipo de serviço"
- A validação de unicidade não ignorava a própria matrícula sendo editada

### Correções Aplicadas

#### 2.1. Frontend - Diferenciar Criação de Edição
**Arquivo:** `admin/pages/alunos.php` (linha ~7162)

**Adicionado:**
```javascript
// Verificar se é edição (já existe matrícula) ou criação
const matriculaId = contextoAlunoAtual.matriculaId || null;
const isEdicao = matriculaId !== null && matriculaId !== undefined;

// Se for edição, adicionar ID da matrícula
if (isEdicao) {
    dadosMatricula.id = parseInt(matriculaId);
}

// Se for edição, usar PUT; se for criação, usar POST
const url = isEdicao 
    ? `api/matriculas.php?id=${matriculaId}&t=${timestamp}`
    : `api/matriculas.php?t=${timestamp}`;
const method = isEdicao ? 'PUT' : 'POST';
```

#### 2.2. Backend - Validação de Unicidade Ignora Matrícula Atual
**Arquivo:** `admin/api/matriculas.php` (linha ~192)

**Antes:**
```php
// Validação não diferenciava criação de edição
$matriculaExistente = $db->fetch("
    SELECT id FROM matriculas 
    WHERE aluno_id = ? AND categoria_cnh = ? AND tipo_servico = ? AND status = 'ativa'
", [$input['aluno_id'], $input['categoria_cnh'], $input['tipo_servico']]);
```

**Depois:**
```php
// Verificar unicidade: não pode haver outra matrícula ativa com mesmo aluno + categoria + tipo
// (ignorando a própria matrícula que está sendo editada)
$statusNovo = $input['status'] ?? $matricula['status'];
$categoriaNova = $input['categoria_cnh'] ?? $matricula['categoria_cnh'];
$tipoServicoNovo = $input['tipo_servico'] ?? $matricula['tipo_servico'];

// Só validar unicidade se o status for 'ativa'
if ($statusNovo === 'ativa') {
    $matriculaConflitante = $db->fetch("
        SELECT id FROM matriculas 
        WHERE aluno_id = ? 
          AND categoria_cnh = ? 
          AND tipo_servico = ? 
          AND status = 'ativa'
          AND id <> ?  // ✅ Ignora a própria matrícula
    ", [$matricula['aluno_id'], $categoriaNova, $tipoServicoNovo, $id]);
    
    if ($matriculaConflitante) {
        http_response_code(400);
        echo json_encode([
            'success' => false, 
            'error' => 'Já existe uma matrícula ativa para esta categoria e tipo de serviço'
        ]);
        return;
    }
}
```

---

## 3. Instrutor Principal - Tornar Opcional

### Problema Identificado
- Campo "Instrutor Principal" na vinculação prática poderia estar bloqueando o salvamento da matrícula

### Correções Aplicadas

#### 3.1. Frontend - Campo Já Estava Opcional
**Arquivo:** `admin/pages/alunos.php` (linha ~2541)

**Status:** ✅ Campo já não tinha atributo `required` no HTML

```html
<select class="form-select" id="instrutor_principal_id" name="instrutor_principal_id">
    <option value="">Selecione...</option>
</select>
```

#### 3.2. Backend - Incluir Campo no Payload (Opcional)
**Arquivo:** `admin/pages/alunos.php` (linha ~7172)

**Adicionado:**
```javascript
const dadosMatricula = {
    // ... outros campos
    instrutor_principal_id: formData.get('instrutor_principal_id') || null  // ✅ Opcional
};
```

**Nota:** O campo `instrutor_principal_id` não é salvo na tabela `matriculas` atualmente, pois não existe essa coluna. Se necessário no futuro, será adicionada via migration.

---

## 4. Data de Conclusão - Automática e Não Obrigatória

### Problema Identificado
- Campo "Data de Conclusão" poderia ser preenchido manualmente
- Não havia lógica para preencher automaticamente quando a matrícula fosse concluída

### Correções Aplicadas

#### 4.1. Frontend - Campo Readonly com Placeholder Explicativo
**Arquivo:** `admin/pages/alunos.php` (linha ~2408)

**Antes:**
```html
<input type="date" class="form-control" id="data_conclusao" name="data_conclusao">
```

**Depois:**
```html
<input type="date" class="form-control" id="data_conclusao" name="data_conclusao" 
       placeholder="Preenchida automaticamente quando a matrícula for concluída" 
       readonly style="background-color: #f8f9fa; cursor: not-allowed;"
       title="Este campo é preenchido automaticamente quando o status da matrícula muda para 'Concluída'">
```

#### 4.2. Frontend - Tornar Readonly ao Carregar Matrícula
**Arquivo:** `admin/pages/alunos.php` (linha ~7766)

**Adicionado:**
```javascript
// Data de conclusão - somente leitura (preenchida automaticamente)
const dataConclusaoInput = document.getElementById('data_conclusao');
if (dataConclusaoInput) {
    if (matricula.data_fim) {
        dataConclusaoInput.value = matricula.data_fim;
    }
    // Tornar campo readonly com placeholder explicativo
    dataConclusaoInput.readOnly = true;
    dataConclusaoInput.placeholder = 'Preenchida automaticamente quando a matrícula for concluída';
    dataConclusaoInput.title = 'Este campo é preenchido automaticamente quando o status da matrícula muda para "Concluída"';
}
```

#### 4.3. Backend - Preencher Automaticamente ao Mudar Status para "Concluída"
**Arquivo:** `admin/api/matriculas.php` (linha ~201)

**Adicionado:**
```php
// Lógica para data_conclusao automática
$statusAnterior = $matricula['status'];
$dataFimAnterior = $matricula['data_fim'];

// Se status mudou para 'concluida' e data_fim está vazia, preencher automaticamente
if ($statusNovo === 'concluida' && $statusAnterior !== 'concluida') {
    if (empty($dataFimAnterior) && empty($input['data_fim'])) {
        // Preencher automaticamente
        $dadosUpdate['data_fim'] = date('Y-m-d');
    } else {
        // Manter data existente ou usar a fornecida
        $dadosUpdate['data_fim'] = $input['data_fim'] ?? $dataFimAnterior;
    }
} else {
    // Se não mudou para concluida, usar valor fornecido ou manter existente
    $dadosUpdate['data_fim'] = $input['data_fim'] ?? $dataFimAnterior;
}
```

**Regras:**
- ✅ Se status muda para "concluida" e `data_fim` está vazia → preenche automaticamente com data atual
- ✅ Se já existe `data_fim` → mantém a data existente (não sobrescreve)
- ✅ Se usuário forneceu `data_fim` → usa a data fornecida

---

## 5. Resumo das Alterações

### Arquivos Modificados

1. **`admin/pages/alunos.php`**
   - Linha ~7162: Adicionado RENACH e campos do processo ao payload
   - Linha ~7177: Diferenciar POST (criação) de PUT (edição)
   - Linha ~2408: Campo `data_conclusao` tornando readonly
   - Linha ~7772: Preencher RENACH e campos do processo ao carregar matrícula
   - Linha ~5051: Modal Detalhes usa RENACH da matrícula com prioridade

2. **`admin/api/matriculas.php`**
   - Linha ~144: Incluir RENACH e campos do processo no INSERT
   - Linha ~192: Validação de unicidade ignora matrícula atual na edição
   - Linha ~201: Lógica para preencher `data_fim` automaticamente quando status muda para "concluida"
   - Linha ~202: Incluir RENACH e campos do processo no UPDATE

3. **`admin/api/alunos.php`**
   - Linha ~403: Buscar matrícula ativa e incluir RENACH e outros dados no retorno da API

---

## 6. Testes Realizados e Esperados

### 6.1. Teste: RENACH Salvo e Exibido Corretamente

**Passos:**
1. Criar matrícula preenchendo:
   - Curso/serviço (categoria + tipo)
   - Data da matrícula
   - RENACH
2. Salvar matrícula
3. Reabrir edição do aluno
4. Verificar aba Matrícula
5. Abrir modal Detalhes

**Resultado Esperado:**
- ✅ RENACH aparece preenchido na aba Matrícula
- ✅ RENACH aparece no modal Detalhes (não mais "Não informado")

### 6.2. Teste: Editar Matrícula Sem Erro de Duplicidade

**Passos:**
1. Criar matrícula ativa para um aluno (categoria X, tipo Y)
2. Voltar na aba Matrícula
3. Editar alguns campos (ex.: observações, valores)
4. Salvar

**Resultado Esperado:**
- ✅ Não aparece erro "já existe uma matrícula ativa..."
- ✅ Matrícula é atualizada normalmente

### 6.3. Teste: Salvar Matrícula Sem Instrutor Principal

**Passos:**
1. Criar/editar matrícula
2. Não preencher campo "Instrutor Principal"
3. Salvar

**Resultado Esperado:**
- ✅ Matrícula salva sem erro
- ✅ Campo permanece vazio (opcional)

### 6.4. Teste: Data de Conclusão Automática

**Passos:**
1. Criar matrícula com data de matrícula e sem data de conclusão
2. Salvar
3. Verificar no banco: `data_fim` = NULL
4. Editar status da matrícula para "Concluída"
5. Salvar
6. Verificar no banco: `data_fim` preenchida automaticamente
7. Reabrir edição/Detalhes

**Resultado Esperado:**
- ✅ `data_fim` = NULL na criação
- ✅ `data_fim` preenchida automaticamente ao mudar status para "Concluída"
- ✅ Data aparece em edição e Detalhes

---

## 7. Estrutura de Dados

### 7.1. Payload Enviado para API (POST/PUT)

```javascript
{
    aluno_id: 167,
    categoria_cnh: "B",
    tipo_servico: "primeira_habilitacao",
    data_inicio: "2024-01-15",
    data_fim: null,  // Opcional, preenchido automaticamente quando status = "concluida"
    status: "ativa",
    valor_total: 3500.00,
    forma_pagamento: "Boleto",
    observacoes: null,
    renach: "PE123456789",  // ✅ Novo campo
    processo_numero: null,  // ✅ Novo campo
    processo_numero_detran: null,  // ✅ Novo campo
    processo_situacao: null,  // ✅ Novo campo
    instrutor_principal_id: null  // ✅ Novo campo (opcional)
}
```

### 7.2. Resposta da API GET (alunos.php)

```json
{
    "success": true,
    "aluno": {
        "id": 167,
        "nome": "João Silva",
        "renach": "PE123456789",  // RENACH do aluno (tabela alunos)
        "renach_matricula": "PE987654321",  // ✅ RENACH da matrícula ativa (prioridade)
        "numero_processo": "12345",
        "detran_numero": "DETRAN-67890",
        "processo_situacao": "em_analise",
        "status_matricula": "ativa",
        "data_conclusao": null
    }
}
```

---

## 8. Observações Importantes

### 8.1. RENACH - Prioridade
- **RENACH da matrícula** tem prioridade sobre RENACH do aluno
- Se matrícula tiver RENACH, ele é usado no modal Detalhes
- Se não houver RENACH na matrícula, usa RENACH do aluno

### 8.2. Validação de Unicidade
- Apenas valida se status for "ativa"
- Ignora a própria matrícula sendo editada
- Permite múltiplas matrículas inativas para o mesmo aluno + categoria + tipo

### 8.3. Data de Conclusão
- Campo é **readonly** no frontend
- Preenchida automaticamente quando status muda para "concluida"
- Se já houver data, não sobrescreve (permite ajuste manual via banco se necessário)

### 8.4. Instrutor Principal
- Campo é **opcional** (não bloqueia salvamento)
- Não é salvo na tabela `matriculas` atualmente
- Pode ser implementado no futuro se necessário

---

## 9. Próximos Passos (Futuro)

1. **Instrutor Principal:**
   - Adicionar coluna `instrutor_principal_id` na tabela `matriculas` (se necessário)
   - Implementar lógica de vinculação prática

2. **Melhorias na Validação:**
   - Adicionar validação de formato do RENACH
   - Validar unicidade de RENACH entre alunos diferentes

3. **Histórico de Alterações:**
   - Registrar quando `data_fim` foi preenchida automaticamente
   - Log de mudanças de status da matrícula

---

**Documento criado para referência técnica e validação dos testes.**

