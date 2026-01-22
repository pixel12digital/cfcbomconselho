# Investigação: Erro 400 na Matrícula do Aluno 167 na Turma 16

## Problema Reportado

Ao clicar no botão "Matricular" no modal "Matricular Alunos na Turma" para o aluno 167 (Charles) na turma 16, a API retorna HTTP 400 (Bad Request).

**Console do Chrome:**
```
Failed to load resource: the server responded with a status of 400 (Bad Request)
.../admin/api/matricular-aluno-turma.php:1
```

## Contexto

- **Turma 16:** `cfc_id = 36` (CFC canônico)
- **Aluno 167 (Charles):** 
  - `cfc_id = 36` (CFC canônico)
  - Status: ativo
  - Exames: médico e psicotécnico concluídos e aprovados (21/11/2025)
  - Financeiro: liberado (sem pendências)

## Causa Raiz Identificada

### Problema 1: Validação de Exames Incompatível

A API `admin/api/matricular-aluno-turma.php` estava verificando exames diretamente com:

```php
if (!$medico || $medico['resultado'] !== 'apto') {
    throw new Exception('Aluno não possui exame médico aprovado');
}
```

**Problema:** O aluno 167 tem exames com `resultado = 'aprovado'` (valor antigo), mas a API só aceitava `'apto'` (valor novo).

**Solução:** Substituir pela função centralizada `GuardsExames::alunoComExamesOkParaTeoricas($alunoId)`, que já trata a compatibilidade entre valores antigos ('aprovado') e novos ('apto').

### Problema 2: Falta de Validação Financeira

A API não estava verificando o financeiro antes de matricular o aluno.

**Solução:** Adicionar validação usando `FinanceiroAlunoHelper::verificarPermissaoFinanceiraAluno($alunoId)`.

### Problema 3: Respostas de Erro Não Amigáveis

A API retornava HTTP 400 "seco", dificultando o tratamento no frontend.

**Solução:** Retornar HTTP 200 com `{ success: false, message: '...' }` para permitir tratamento mais amigável no JS.

## Correções Implementadas

### 1. API (`admin/api/matricular-aluno-turma.php`)

#### Inclusão de Guards Centralizados
```php
require_once __DIR__ . '/../includes/guards_exames.php';
require_once __DIR__ . '/../includes/FinanceiroAlunoHelper.php';
```

#### Logs Detalhados Adicionados
```php
error_log('[MATRICULAR_ALUNO_TURMA] ===============================');
error_log('[MATRICULAR_ALUNO_TURMA] REQUEST METHOD: ' . $_SERVER['REQUEST_METHOD']);
error_log('[MATRICULAR_ALUNO_TURMA] RAW php://input: ' . file_get_contents('php://input'));
error_log('[MATRICULAR_ALUNO_TURMA] alunoId: ' . ($alunoId ?? 'NULL'));
error_log('[MATRICULAR_ALUNO_TURMA] turmaId: ' . ($turmaId ?? 'NULL'));
```

#### Substituição da Validação de Exames
**Antes:**
```php
if (!$medico || $medico['resultado'] !== 'apto') {
    throw new Exception('Aluno não possui exame médico aprovado');
}
```

**Depois:**
```php
$examesOK = GuardsExames::alunoComExamesOkParaTeoricas($alunoId);
if (!$examesOK) {
    throw new Exception('Aluno não possui exames médico e psicotécnico concluídos e aprovados');
}
```

#### Adição de Validação Financeira
```php
$financeiro = FinanceiroAlunoHelper::verificarPermissaoFinanceiraAluno($alunoId);
if (!$financeiro['liberado']) {
    throw new Exception($financeiro['motivo'] ?? 'Aluno com pendências financeiras');
}
```

#### Padronização de Respostas de Erro
**Antes:**
```php
http_response_code(400);
echo json_encode(['sucesso' => false, 'mensagem' => $e->getMessage()]);
```

**Depois:**
```php
http_response_code(200); // Retornar 200 com success=false para melhor tratamento
echo json_encode([
    'sucesso' => false, 
    'mensagem' => $mensagemErro
], JSON_UNESCAPED_UNICODE);
```

#### Logs de Erro Específicos
Cada ponto de validação agora tem log específico:
```php
error_log('[MATRICULAR_ALUNO_TURMA] ERRO 400 - motivo: parâmetros obrigatórios ausentes');
error_log('[MATRICULAR_ALUNO_TURMA] ERRO 400 - motivo: exames não OK');
error_log('[MATRICULAR_ALUNO_TURMA] ERRO 400 - motivo: financeiro bloqueado');
```

### 2. Frontend (`admin/pages/turmas-teoricas-detalhes-inline.php`)

#### Logs Adicionados no JS
```javascript
console.log('[MATRICULAR_ALUNO] Enviando requisição', {
    url: 'api/matricular-aluno-turma.php',
    turmaId: turmaId,
    alunoId: alunoId,
    payload: payload
});
```

#### Melhor Tratamento de Respostas
```javascript
.then(async response => {
    // Tentar parsear JSON mesmo se status não for 200
    let data = null;
    try {
        const text = await response.text();
        data = text ? JSON.parse(text) : null;
    } catch (e) {
        console.error('[MATRICULAR_ALUNO] Erro ao parsear resposta JSON:', e);
        throw { tipo: 'parse', mensagem: 'Erro ao processar resposta do servidor' };
    }
    
    console.log('[MATRICULAR_ALUNO] Resposta recebida', {
        status: response.status,
        ok: response.ok,
        data: data
    });
    
    if (!data || !data.sucesso) {
        const mensagemErro = data?.mensagem || 'Não foi possível matricular o aluno.';
        throw { tipo: 'api', mensagem: mensagemErro };
    }
    
    return data;
})
```

## Validações Implementadas

A API agora valida, nesta ordem:

1. ✅ **Parâmetros obrigatórios:** `aluno_id` e `turma_id` presentes
2. ✅ **Turma existe:** Turma encontrada no banco
3. ✅ **Status da turma:** Turma em status que permite matrícula (agendando, completa, ativa)
4. ✅ **Vagas disponíveis:** Turma não está lotada
5. ✅ **Aluno existe:** Aluno encontrado no banco
6. ✅ **CFC compatível:** `aluno.cfc_id === turma.cfc_id` (ou admin global pode ajustar)
7. ✅ **Aluno ativo:** `aluno.status = 'ativo'`
8. ✅ **Não matriculado:** Aluno não está já matriculado nesta turma
9. ✅ **Exames OK:** `GuardsExames::alunoComExamesOkParaTeoricas($alunoId)` retorna `true`
10. ✅ **Financeiro OK:** `FinanceiroAlunoHelper::verificarPermissaoFinanceiraAluno($alunoId)` retorna `liberado = true`

## Exemplo de Payload

### Requisição (JS → API)
```json
{
  "aluno_id": 167,
  "turma_id": 16
}
```

### Resposta de Sucesso
```json
{
  "sucesso": true,
  "mensagem": "Aluno Charles Dietrich Wutzke matriculado com sucesso na turma Turma A - Formação CNH AB",
  "dados": {
    "matricula_id": 123,
    "aluno": {
      "id": 167,
      "nome": "Charles Dietrich Wutzke",
      "cpf": "...",
      "categoria_cnh": "...",
      "cfc_nome": "CFC BOM CONSELHO",
      "email": "...",
      "telefone": "..."
    },
    "matricula": {
      "id": 123,
      "status": "matriculado",
      "data_matricula": "2025-11-21 10:30:00"
    },
    "turma": {
      "id": 16,
      "nome": "Turma A - Formação CNH AB",
      "alunos_matriculados": 1
    }
  }
}
```

### Resposta de Erro (Regra de Negócio)
```json
{
  "sucesso": false,
  "mensagem": "Aluno não possui exames médico e psicotécnico concluídos e aprovados"
}
```

## Logs Esperados

### Sucesso
```
[MATRICULAR_ALUNO_TURMA] ===============================
[MATRICULAR_ALUNO_TURMA] REQUEST METHOD: POST
[MATRICULAR_ALUNO_TURMA] alunoId: 167
[MATRICULAR_ALUNO_TURMA] turmaId: 16
[MATRICULAR_ALUNO_TURMA] Turma encontrada: id=16, nome=Turma A - Formação CNH AB, cfc_id=36
[MATRICULAR_ALUNO_TURMA] Aluno encontrado: id=167, nome=Charles Dietrich Wutzke, cfc_id=36, status=ativo
[MATRICULAR_ALUNO_TURMA] ✅ CFC compatível: aluno e turma ambos com cfc_id=36
[MATRICULAR_ALUNO_TURMA] Verificando exames do aluno 167 usando guard centralizado...
[MATRICULAR_ALUNO_TURMA] ✅ Exames OK para aulas teóricas
[MATRICULAR_ALUNO_TURMA] Verificando financeiro do aluno 167...
[MATRICULAR_ALUNO_TURMA] ✅ Financeiro OK - aluno liberado para avançar
```

### Erro (Exemplo: Exames não OK)
```
[MATRICULAR_ALUNO_TURMA] ERRO 400 - motivo: exames não OK para aulas teóricas. alunoId=167
[MATRICULAR_ALUNO_TURMA] Stack trace: ...
```

## Teste do Cenário Específico

### Aluno 167 na Turma 16

**Estado:**
- Turma 16: CFC 36, status "criando" (ou "agendando"/"completa"/"ativa")
- Aluno 167: CFC 36, ativo, exames OK, financeiro OK

**Resultado Esperado:**
- ✅ API retorna HTTP 200 com `{ success: true, ... }`
- ✅ Matrícula criada no banco
- ✅ Modal fecha ou atualiza lista
- ✅ Aluno aparece na lista de matriculados

## Arquivos Modificados

1. **`admin/api/matricular-aluno-turma.php`**
   - Inclusão de guards centralizados
   - Substituição de validação de exames
   - Adição de validação financeira
   - Logs detalhados
   - Padronização de respostas

2. **`admin/pages/turmas-teoricas-detalhes-inline.php`**
   - Logs adicionados no JS
   - Melhor tratamento de respostas
   - Tratamento de erros aprimorado

## Garantias

✅ **Reuso de guards centralizados:** Usa `GuardsExames` e `FinanceiroAlunoHelper`  
✅ **Compatibilidade:** Trata valores antigos ('aprovado') e novos ('apto')  
✅ **Respostas amigáveis:** HTTP 200 com JSON claro  
✅ **Logs detalhados:** Facilita debug e auditoria  
✅ **CFC dinâmico:** Não assume valores fixos, usa CFC do banco  

---

**Data:** 2025-11-21  
**Status:** ✅ Correções implementadas

