# Auditoria: Fluxo de Alteração de Status do Aluno (Ativo/Inativo/Concluído)

**Data da Auditoria:** 2025-01-27  
**Sistema:** CFC Bom Conselho  
**Objetivo:** Identificar onde está quebrando o fluxo de alteração de status do aluno

---

## 1. Objetivo

Esta auditoria visa mapear e documentar os dois caminhos existentes para alterar o status do aluno (Ativo/Inativo/Concluído) e identificar por que as alterações não estão sendo persistidas no banco de dados, resultando em alunos que continuam aparecendo como "ATIVO" na listagem mesmo após tentativas de desativação.

---

## 2. Estrutura de Banco Relacionada ao Status do Aluno

### 2.1. Tabela Principal: `alunos`

**Localização da definição:** `install.php` (linha 58-72)

**Estrutura do campo de status:**
- **Nome do campo:** `status`
- **Tipo de dado:** `ENUM('ativo', 'inativo', 'concluido')`
- **Valor padrão:** `'ativo'`
- **Posição na tabela:** Após `categoria_cnh`

**SQL de criação:**
```sql
CREATE TABLE IF NOT EXISTS alunos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    cpf VARCHAR(14) UNIQUE NOT NULL,
    ...
    categoria_cnh ENUM('A', 'B', 'C', 'D', 'E', 'AB', 'AC', 'AD', 'AE') NOT NULL,
    status ENUM('ativo', 'inativo', 'concluido') DEFAULT 'ativo',
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ...
)
```

**Valores permitidos:**
- `'ativo'` - Aluno ativo no sistema
- `'inativo'` - Aluno desativado (não pode agendar aulas)
- `'concluido'` - Aluno que concluiu o processo

### 2.2. Tabelas Relacionadas

**Tabelas que podem depender do status do aluno:**
- `matriculas` - Possui campo `status` próprio (ENUM: 'ativa', 'concluida', 'trancada', 'cancelada')
- `aulas` - Vinculadas ao `aluno_id`, mas não possuem campo de status do aluno
- `exames` - Vinculados ao `aluno_id`, mas não possuem campo de status do aluno

**Observação:** O status do aluno na tabela `alunos` é independente do status da matrícula. A listagem de alunos usa o campo `alunos.status` para exibir o badge de status.

---

## 3. Fluxo 1 – Ação Rápida "Desativar aluno"

### 3.1. Identificação do Botão

**Arquivo:** `admin/pages/alunos.php`

**Localização do HTML:**
- **Linha 1665-1666:** Botão na coluna de ações (versão desktop)
- **Linha 1745:** Botão na versão mobile

**Código HTML:**
```html
<!-- Desktop -->
<button type="button" 
        class="btn btn-sm btn-outline-danger" 
        onclick="desativarAluno(<?php echo $aluno['id']; ?>)" 
        title="Desativar aluno (não poderá agendar aulas)" 
        data-bs-toggle="tooltip">
    <i class="fas fa-ban"></i>
</button>

<!-- Mobile -->
<button type="button" 
        class="btn btn-sm btn-outline-secondary" 
        onclick="desativarAluno(<?php echo $aluno['id']; ?>)" 
        title="Desativar aluno">
    Desativar
</button>
```

**Condição de exibição:** O botão só aparece se `$aluno['status'] === 'ativo'` (linhas 1663 e 1744)

### 3.2. Mapeamento do JavaScript

**Arquivo:** `admin/pages/alunos.php` (código JavaScript inline, linha 5555-5559)

**Função `desativarAluno(id)`:**
```javascript
function desativarAluno(id) {
    if (confirm('Deseja realmente desativar este aluno? Esta ação pode afetar o histórico de aulas.')) {
        alterarStatusAluno(id, 'inativo');
    }
}
```

**Função `alterarStatusAluno(id, status)`:**
```javascript
function alterarStatusAluno(id, status) {
    const mensagem = `Deseja realmente ${status === 'ativo' ? 'ativar' : 'desativar'} este aluno?`;
    
    if (confirm(mensagem)) {
        if (typeof loading !== 'undefined') {
            loading.showGlobal('Alterando status...');
        }
        
        const formData = new FormData();
        formData.append('acao', 'alterar_status');
        formData.append('aluno_id', id);
        formData.append('status', status);
        
        fetch('pages/alunos.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(data => {
            if (typeof loading !== 'undefined') {
                loading.hideGlobal();
            }
            if (typeof notifications !== 'undefined') {
                notifications.success(`Status do aluno alterado para ${status} com sucesso!`);
            }
            location.reload();
        })
        .catch(error => {
            if (typeof loading !== 'undefined') {
                loading.hideGlobal();
            }
            console.error('Erro:', error);
            if (typeof notifications !== 'undefined') {
                notifications.error('Erro ao alterar status do aluno');
            } else {
                mostrarAlerta('Erro ao alterar status do aluno', 'danger');
            }
        });
    }
}
```

**Resumo do fluxo JavaScript:**
1. Usuário clica no botão "Desativar aluno"
2. `desativarAluno(id)` é chamada
3. Exibe `confirm()` perguntando confirmação
4. Se confirmado, chama `alterarStatusAluno(id, 'inativo')`
5. `alterarStatusAluno` exibe outro `confirm()` (duplicado)
6. Se confirmado, cria `FormData` com:
   - `acao`: `'alterar_status'`
   - `aluno_id`: ID do aluno
   - `status`: `'inativo'`
7. Faz `fetch` POST para `pages/alunos.php`
8. Mostra mensagem de sucesso e recarrega a página

### 3.3. Mapeamento do Endpoint PHP

**❌ PROBLEMA IDENTIFICADO:** A função JavaScript faz POST para `pages/alunos.php`, mas esse arquivo **NÃO processa a ação `alterar_status`**.

**Arquivo:** `admin/pages/alunos.php`

**Análise do código PHP:**
- O arquivo `alunos.php` é uma página de visualização que renderiza HTML
- Ele não possui lógica para processar POST com `acao=alterar_status`
- O arquivo verifica apenas se está sendo incluído pelo sistema de roteamento (linha 3)
- Não há tratamento de `$_POST['acao']` ou `$_POST['status']` no arquivo

**Busca realizada:**
```bash
grep -i "alterar_status\|acao.*alterar\|POST.*status" admin/pages/alunos.php
```
**Resultado:** Nenhuma correspondência encontrada para processamento de `alterar_status`

**Conclusão:** O endpoint `pages/alunos.php` não processa a requisição de alteração de status. A requisição é enviada, mas não há código PHP para processá-la, resultando em:
- A resposta HTTP provavelmente retorna o HTML completo da página
- O JavaScript interpreta como sucesso (status 200)
- A mensagem de sucesso é exibida
- A página é recarregada
- **Mas o status no banco não foi alterado**

### 3.4. Verificação do UPDATE no Banco

**Status:** ❌ **NÃO EXECUTADO**

Como o endpoint `pages/alunos.php` não processa a ação `alterar_status`, nenhum UPDATE é executado no banco de dados.

**SQL esperado (mas não executado):**
```sql
UPDATE alunos 
SET status = 'inativo' 
WHERE id = ?
```

### 3.5. Comportamento Atual Observado

**Sintomas:**
1. ✅ Usuário clica no botão "Desativar aluno"
2. ✅ `confirm()` é exibido
3. ✅ Após confirmação, mensagem de sucesso aparece
4. ❌ Página recarrega, mas o status continua "ATIVO"
5. ❌ O aluno não é desativado no banco de dados

**Causa raiz:** A requisição POST é enviada para um endpoint que não processa essa ação.

---

## 4. Fluxo 2 – Edição de Status no Modal "Editar Aluno"

### 4.1. Identificação do Formulário

**Arquivo:** `admin/pages/alunos.php`

**Localização do campo de status:**
- **Linha 2256-2261:** Campo select dentro do modal `#modalAluno`, aba "Dados"

**Código HTML:**
```html
<div class="col-md-6">
    <div class="mb-2">
        <label for="status" class="form-label" style="font-size: 0.8rem; margin-bottom: 0.1rem;">Status do Aluno</label>
        <select class="form-select" id="status" name="status" style="padding: 0.4rem; font-size: 0.85rem;">
            <option value="ativo">Ativo</option>
            <option value="inativo">Inativo</option>
            <option value="concluido">Concluído</option>
        </select>
    </div>
</div>
```

**ID do formulário:** `formAluno` (não encontrado no trecho lido, mas referenciado no JavaScript)

**Name do campo:** `status`

**Valores das opções:**
- `ativo` - Ativo
- `inativo` - Inativo
- `concluido` - Concluído

### 4.2. Mapeamento do Envio do Formulário

**Arquivo JavaScript:** `admin/assets/js/alunos.js`

**Função `salvarAluno()` (linha 194-307):**

**Trecho relevante:**
```javascript
window.salvarAluno = async function() {
    console.log('💾 Salvando aluno...');
    
    try {
        const form = document.getElementById('formAluno');
        if (!form) {
            throw new Error('Formulário não encontrado');
        }
        
        const formData = new FormData(form);
        
        // ... validações ...
        
        // Preparar dados
        const alunoData = {
            nome: (formData.get('nome') || '').trim(),
            cpf: (formData.get('cpf') || '').trim(),
            // ... outros campos ...
            status: formData.get('status') || 'ativo',  // LINHA 245
            // ... outros campos ...
        };
        
        // ... código de envio ...
        
        const acao = formData.get('acao');
        const aluno_id = formData.get('aluno_id');
        
        if (acao === 'editar' && aluno_id) {
            alunoData.id = aluno_id;
        }
        
        // ... mostrar loading ...
        
        const method = acao === 'editar' ? 'PUT' : 'POST';
        const endpoint = acao === 'editar' ? `?id=${aluno_id}` : '';
        
        const response = await fetchAPIAlunos(endpoint, {
            method: method,
            body: JSON.stringify(alunoData)
        });
        
        // ... tratamento de resposta ...
    }
}
```

**Resumo do fluxo:**
1. Usuário altera o campo `status` no modal
2. Clica em "Salvar"
3. `salvarAluno()` é chamada
4. Lê o valor do campo: `formData.get('status')`
5. Inclui no objeto `alunoData`: `status: formData.get('status') || 'ativo'`
6. Se `acao === 'editar'`, faz PUT para `admin/api/alunos.php?id={aluno_id}`
7. Envia JSON com todos os dados, incluindo `status`

### 4.3. Mapeamento do Endpoint PHP / Função de Salvar Aluno

**Arquivo:** `admin/api/alunos.php`

**Fluxo de UPDATE (POST com id na query string):**

**Linha 537-839:** Processamento de UPDATE

**Trecho relevante (linha 730-812):**
```php
// Lista de campos permitidos para atualização
$camposPermitidos = [
    'nome', 'cpf', 'rg', 'rg_orgao_emissor', 'rg_uf', 'rg_data_emissao', 'renach',
    'data_nascimento', 'estado_civil', 'profissao', 'escolaridade',
    'naturalidade', 'nacionalidade', 'telefone', 'telefone_secundario',
    'contato_emergencia_nome', 'contato_emergencia_telefone', 'email',
    'endereco', 'numero', 'bairro', 'cidade', 'estado', 'cep',
    'categoria_cnh', 'tipo_servico', 'status', 'observacoes',  // ✅ 'status' está na lista
    'atividade_remunerada', 'lgpd_consentimento', 'lgpd_consentimento_em',
    'numero_processo', 'detran_numero', 'status_matricula', 'processo_situacao',
    'status_pagamento'
];

// Montar array de campos para atualização
$alunoData = [];
foreach ($camposPermitidos as $campo) {
    if (isset($data[$campo])) {
        $alunoData[$campo] = $data[$campo];
    }
}

// ... processamento de foto ...

// Executar UPDATE
try {
    $resultado = $db->update('alunos', $alunoData, 'id = ?', [$id]);
    
    if (!$resultado) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Erro ao atualizar aluno']);
        exit;
    }
    
    $response = ['success' => true, 'message' => 'Aluno atualizado com sucesso'];
    sendJsonResponse($response);
    
} catch (Exception $e) {
    // ... tratamento de erro ...
}
```

**Análise:**
1. ✅ O campo `status` está na lista de `$camposPermitidos` (linha 737)
2. ✅ Se `$data['status']` estiver definido, será incluído em `$alunoData`
3. ✅ O UPDATE é executado: `$db->update('alunos', $alunoData, 'id = ?', [$id])`

**SQL executado (esperado):**
```sql
UPDATE alunos 
SET status = ?, 
    nome = ?, 
    cpf = ?, 
    ... (outros campos)
WHERE id = ?
```

### 4.4. Verificação se o Status Está Sendo Ignorado

**Análise do código:**

1. **Leitura do campo no JavaScript:**
   - ✅ `formData.get('status')` é lido corretamente (linha 245 de `alunos.js`)
   - ✅ Valor padrão: `'ativo'` se vazio

2. **Envio para API:**
   - ✅ Campo `status` é incluído no JSON enviado
   - ✅ Método PUT é usado para edição

3. **Processamento no PHP:**
   - ✅ Campo `status` está na lista de permitidos
   - ✅ Se presente em `$data`, será incluído no UPDATE

4. **Possíveis problemas:**
   - ⚠️ **Verificar se o campo está sendo preenchido corretamente no modal ao editar**
   - ⚠️ **Verificar se há algum código que sobrescreve o status após o UPDATE**

**Função `editarAluno(id)` (alunos.js, linha 310-501):**

**Trecho relevante (linha 386):**
```javascript
if (statusField) statusField.value = aluno.status || 'ativo';
```

**Análise:** O campo é preenchido corretamente com o valor do banco ao abrir o modal.

### 4.5. Comportamento Atual

**Sintomas relatados:**
1. ✅ Usuário abre modal "Editar Aluno"
2. ✅ Campo "Status do Aluno" é exibido com valor atual
3. ✅ Usuário altera para "Inativo"
4. ✅ Clica em "Salvar"
5. ✅ Mensagem de sucesso aparece
6. ❌ Página recarrega, mas o status continua "ATIVO"

**Possíveis causas:**
1. O campo `status` pode não estar sendo enviado corretamente no FormData
2. O valor pode estar sendo sobrescrito após o UPDATE
3. A listagem pode estar usando uma query diferente que não reflete o UPDATE
4. Pode haver cache ou problema de sincronização

**Próximos passos para investigação:**
- Verificar logs do servidor durante o UPDATE
- Verificar se o valor está sendo enviado no Network tab do navegador
- Verificar se o UPDATE realmente está sendo executado no banco
- Verificar a query usada na listagem de alunos

---

## 5. Comparação dos Dois Fluxos

### 5.1. Endpoints Utilizados

| Fluxo | Endpoint | Método | Ação |
|-------|----------|--------|------|
| **Fluxo 1** (Botão rápido) | `pages/alunos.php` | POST | `acao=alterar_status` |
| **Fluxo 2** (Modal) | `admin/api/alunos.php?id={id}` | PUT | Atualização completa do aluno |

### 5.2. Processamento PHP

| Fluxo | Processa a requisição? | UPDATE executado? |
|-------|------------------------|-------------------|
| **Fluxo 1** | ❌ NÃO | ❌ NÃO |
| **Fluxo 2** | ✅ SIM | ✅ SIM (teoricamente) |

### 5.3. Possíveis Causas do Problema

#### 5.3.1. Fluxo 1 - Botão Rápido

**Causa identificada:**
- ❌ **Endpoint incorreto:** A requisição é enviada para `pages/alunos.php`, que não processa a ação `alterar_status`
- ❌ **Falta de handler PHP:** Não existe código para processar `$_POST['acao'] === 'alterar_status'`

**Solução sugerida:**
- Redirecionar a requisição para `admin/api/alunos.php` usando PUT/PATCH
- Ou adicionar handler em `pages/alunos.php` para processar `alterar_status`

#### 5.3.2. Fluxo 2 - Modal de Edição

**Possíveis causas:**
1. ⚠️ **Campo não sendo enviado:** Verificar se `formData.get('status')` retorna o valor correto
2. ⚠️ **Valor sendo ignorado:** Verificar se há validação que rejeita valores específicos
3. ⚠️ **Sobrescrita após UPDATE:** Verificar se há trigger ou código que altera o status após salvar
4. ⚠️ **Query de listagem incorreta:** Verificar se a listagem usa JOIN ou subquery que pode estar retornando valor antigo
5. ⚠️ **Cache do navegador:** Verificar se há cache que está mostrando valores antigos

**Investigação necessária:**
- Verificar logs do PHP durante o UPDATE
- Verificar Network tab do navegador para ver o payload enviado
- Verificar diretamente no banco se o UPDATE está sendo executado
- Verificar a query SQL usada na listagem

### 5.4. Pontos Suspeitos para Investigação

1. **Fluxo 1:**
   - ❌ Endpoint `pages/alunos.php` não processa `alterar_status`
   - ❌ Requisição retorna HTML em vez de processar a ação

2. **Fluxo 2:**
   - ⚠️ Verificar se o campo `status` está sendo incluído no FormData
   - ⚠️ Verificar se há validação que força `status = 'ativo'`
   - ⚠️ Verificar se a listagem usa cache ou query desatualizada
   - ⚠️ Verificar se há trigger no banco que altera o status

3. **Ambos os fluxos:**
   - ⚠️ Verificar se há código que sincroniza status com matrícula
   - ⚠️ Verificar se há lógica de negócio que impede inativação

---

## 6. Sugestão de Próximos Passos para Correção

### 6.1. Correção Imediata - Fluxo 1

**Problema:** Endpoint incorreto

**Solução 1 (Recomendada):** Modificar `alterarStatusAluno()` para usar a API:
```javascript
function alterarStatusAluno(id, status) {
    const mensagem = `Deseja realmente ${status === 'ativo' ? 'ativar' : 'desativar'} este aluno?`;
    
    if (confirm(mensagem)) {
        if (typeof loading !== 'undefined') {
            loading.showGlobal('Alterando status...');
        }
        
        // Usar a API de alunos
        fetchAPIAlunos(`?id=${id}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                status: status
            })
        })
        .then(response => response.json())
        .then(data => {
            if (typeof loading !== 'undefined') {
                loading.hideGlobal();
            }
            if (data.success) {
                if (typeof notifications !== 'undefined') {
                    notifications.success(`Status do aluno alterado para ${status} com sucesso!`);
                }
                location.reload();
            } else {
                throw new Error(data.error || 'Erro ao alterar status');
            }
        })
        .catch(error => {
            if (typeof loading !== 'undefined') {
                loading.hideGlobal();
            }
            console.error('Erro:', error);
            if (typeof notifications !== 'undefined') {
                notifications.error('Erro ao alterar status do aluno');
            } else {
                mostrarAlerta('Erro ao alterar status do aluno', 'danger');
            }
        });
    }
}
```

**Solução 2 (Alternativa):** Adicionar handler em `pages/alunos.php`:
```php
// No início do arquivo, após verificação de roteamento
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'alterar_status') {
    require_once __DIR__ . '/../includes/database.php';
    require_once __DIR__ . '/../includes/auth.php';
    
    if (!isLoggedIn() || !hasPermission('admin')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Acesso negado']);
        exit;
    }
    
    $aluno_id = (int)($_POST['aluno_id'] ?? 0);
    $status = $_POST['status'] ?? '';
    
    if (!$aluno_id || !in_array($status, ['ativo', 'inativo', 'concluido'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Dados inválidos']);
        exit;
    }
    
    $db = Database::getInstance();
    $resultado = $db->update('alunos', ['status' => $status], 'id = ?', [$aluno_id]);
    
    if ($resultado) {
        echo json_encode(['success' => true, 'message' => 'Status alterado com sucesso']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Erro ao alterar status']);
    }
    exit;
}
```

### 6.2. Investigação - Fluxo 2

**Passos para investigar:**

1. **Verificar payload enviado:**
   - Abrir DevTools → Network
   - Editar aluno e alterar status para "Inativo"
   - Clicar em "Salvar"
   - Verificar a requisição PUT para `admin/api/alunos.php?id={id}`
   - Verificar se o campo `status: "inativo"` está no JSON do body

2. **Verificar resposta da API:**
   - Verificar se retorna `{"success": true}`
   - Verificar se há erros no console do navegador

3. **Verificar banco de dados:**
   - Após salvar, executar: `SELECT id, nome, status FROM alunos WHERE id = {id}`
   - Verificar se o campo `status` foi atualizado

4. **Verificar query de listagem:**
   - Verificar como a listagem de alunos busca os dados
   - Verificar se há JOIN ou subquery que pode estar retornando valor antigo
   - Verificar se há cache sendo usado

5. **Verificar logs do PHP:**
   - Verificar `logs/php_errors.log` ou logs do servidor
   - Procurar por erros durante o UPDATE

### 6.3. Testes Recomendados

**Teste 1 - Fluxo 1:**
1. Clicar no botão "Desativar aluno" para um aluno ativo
2. Confirmar a ação
3. Verificar Network tab - requisição deve ir para `admin/api/alunos.php`
4. Verificar banco: `SELECT status FROM alunos WHERE id = {id}`
5. Verificar listagem - status deve aparecer como "INATIVO"

**Teste 2 - Fluxo 2:**
1. Abrir modal "Editar Aluno"
2. Alterar status de "Ativo" para "Inativo"
3. Clicar em "Salvar"
4. Verificar Network tab - payload deve conter `"status": "inativo"`
5. Verificar resposta da API - deve retornar `{"success": true}`
6. Verificar banco: `SELECT status FROM alunos WHERE id = {id}`
7. Verificar listagem - status deve aparecer como "INATIVO"

**Teste 3 - Verificação de sincronização:**
1. Verificar se há código que sincroniza `alunos.status` com `matriculas.status`
2. Verificar se há trigger no banco que altera o status automaticamente
3. Verificar se há lógica de negócio que impede inativação em certas condições

---

## 7. Observações para Testes em Produção

**Espaço para anotações de testes:**

### Teste Fluxo 1 - Botão Rápido:
- [ ] Requisição enviada para: `_________________`
- [ ] Método HTTP: `_________________`
- [ ] Payload enviado: `_________________`
- [ ] Resposta recebida: `_________________`
- [ ] Status no banco após ação: `_________________`
- [ ] Status exibido na listagem: `_________________`

### Teste Fluxo 2 - Modal de Edição:
- [ ] Valor do campo `status` no formulário: `_________________`
- [ ] Payload JSON enviado: `_________________`
- [ ] Resposta da API: `_________________`
- [ ] Status no banco após salvar: `_________________`
- [ ] Status exibido na listagem: `_________________`

### Logs e Erros:
- [ ] Erros no console do navegador: `_________________`
- [ ] Erros nos logs do PHP: `_________________`
- [ ] Queries SQL executadas: `_________________`

---

## 8. Resumo Executivo

### Problemas Identificados

1. **Fluxo 1 (Botão Rápido):** ❌ **CRÍTICO**
   - Endpoint incorreto: `pages/alunos.php` não processa `alterar_status`
   - Nenhum UPDATE é executado no banco
   - Mensagem de sucesso é exibida incorretamente

2. **Fluxo 2 (Modal de Edição):** ⚠️ **SUSPEITO**
   - Código parece correto (status está na lista de permitidos)
   - UPDATE deveria ser executado
   - Necessária investigação adicional para confirmar o problema

### Prioridade de Correção

1. **ALTA:** Corrigir Fluxo 1 (endpoint incorreto)
2. **MÉDIA:** Investigar e corrigir Fluxo 2 (se confirmado o problema)

### Impacto

- **Usuários afetados:** Todos os usuários que tentam desativar alunos
- **Funcionalidade:** Inativação de alunos não funciona
- **Risco:** Alunos inativos podem continuar agendando aulas (se houver validação baseada em status)

---

**Fim da Auditoria**

