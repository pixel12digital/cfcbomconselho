# Correção: CFC Undefined no Modal de Turmas Teóricas

## Problema Identificado

O modal "Matricular Alunos na Turma" estava exibindo:
- CFC da Turma: `undefined`
- CFC da Sessão: `undefined`
- CFCs coincidem: Não

Isso indicava que as variáveis JavaScript não estavam sendo definidas corretamente.

## Causa Raiz

1. **API não retornava CFC no debug_info:** A API calculava o CFC da turma e da sessão, mas não incluía no `debug_info` retornado ao frontend.

2. **Frontend não tinha acesso ao CFC:** O JavaScript não tinha acesso às variáveis PHP `$turma['cfc_id']` e `$user['cfc_id']`.

## Solução Implementada

### 1. API Ajustada

**Arquivo:** `admin/api/alunos-aptos-turma-simples.php`

**Alterações:**

#### 1.1. Obtenção do CFC da Sessão (linha ~18-45)
- Adicionada função helper `getCurrentUser()` se não existir
- Obtém CFC da sessão através do usuário logado
- Calcula se CFCs coincidem

#### 1.2. Logs Iniciais (linha ~26-45)
```php
error_log("[TURMAS TEORICAS API] Requisição recebida - turma_id: {$turmaId}, input: " . json_encode($input));
error_log("[TURMAS TEORICAS API] CFC da Turma: {$cfcIdTurma}, CFC da Sessão: {$cfcIdSessao}");
```

#### 1.3. Debug Info Completo (linha ~160-175)
```php
$debugInfoCompleto = [
    'turma_cfc_id' => $cfcIdTurma,
    'session_cfc_id' => $cfcIdSessao,
    'cfc_ids_match' => $cfcIdsCoincidem,
    'turma_id' => $turmaId,
    'total_candidatos' => count($alunosCandidatos),
    'total_aptos' => count($alunosAptos),
    'alunos_detalhados' => $debugInfo
];
```

#### 1.4. Logs Detalhados por Candidato (linha ~145-150)
- Inclui CFC da Turma e CFC da Sessão em cada log
- Log específico para aluno 167 mantido

### 2. Frontend Ajustado

**Arquivo:** `admin/pages/turmas-teoricas-detalhes-inline.php`

**Alterações:**

#### 2.1. Variáveis JavaScript (linha ~12721-12723)
```javascript
const TURMA_ID_DETALHES = <?= $turmaId ?>;
const TURMA_CFC_ID = <?= (int)($turma['cfc_id'] ?? 0) ?>;
const SESSION_CFC_ID = <?= (int)($user['cfc_id'] ?? 0) ?>;
```

#### 2.2. Função `carregarAlunosAptos()` (linha ~12960-12975)
- Adicionado log do `debug_info` recebido
- Garantia de valores de CFC no `debug_info`:
  - Se API não retornar, usa valores das constantes JavaScript
  - Calcula `cfc_ids_match` se ambos estiverem presentes

#### 2.3. Função `exibirAlunosAptos()` (linha ~12995-13005)
- Já estava preparada para exibir `debugInfo.turma_cfc_id` e `debugInfo.session_cfc_id`
- Agora recebe valores corretos da API ou das constantes JavaScript

## Arquivos Modificados

### `admin/api/alunos-aptos-turma-simples.php`

1. **Linha ~18-45:** Obtenção do CFC da sessão e logs iniciais
2. **Linha ~43:** Variável renomeada de `$cfcId` para `$cfcIdTurma` para clareza
3. **Linha ~72:** Log atualizado com CFCs
4. **Linha ~145-150:** Logs detalhados por candidato incluem CFCs
5. **Linha ~160-175:** `debug_info` completo com CFCs
6. **Linha ~180:** Log final da resposta

### `admin/pages/turmas-teoricas-detalhes-inline.php`

1. **Linha ~12721-12723:** Constantes JavaScript para CFCs
2. **Linha ~12960-12975:** Garantia de valores de CFC no `debug_info`
3. **Linha ~12962:** Log do `debug_info` recebido

## Logs de Debug

### API - Requisição Inicial
```
[TURMAS TEORICAS API] Requisição recebida - turma_id: {id}, input: {...}
[TURMAS TEORICAS API] CFC da Turma: {id}, CFC da Sessão: {id}
```

### API - Total de Candidatos
```
[TURMAS TEORICAS API] Turma {id} - CFC Turma: {id}, CFC Sessão: {id}, Total candidatos: {n}
```

### API - Por Candidato
```
[TURMAS TEORICAS API] Candidato aluno {id} ({nome}) - CFC Turma: {id}, CFC Sessão: {id}, financeiro_ok={bool}, exames_ok={bool}, categoria_ok={bool}, status_matricula={status}, elegivel={bool}
```

### API - Específico para Aluno 167
```
[TURMAS TEORICAS] Aluno 167 (Charles) - examesOK={bool}, financeiroOK={bool}, categoriaOK={bool}, status_matricula={status}
```

### API - Resposta Final
```
[TURMAS TEORICAS API] Resposta - Total aptos: {n}, CFC Turma: {id}, CFC Sessão: {id}, Coincidem: {Sim/Não}
```

### Frontend
```
[TURMAS TEORICAS FRONTEND] Debug Info recebido: {...}
```

## Testes Esperados

### ✅ Cenário 1: Aluno 167 (Charles)

**Estado:**
- Exames médico e psicotécnico: concluído + apto
- Financeiro: sem faturas vencidas
- CFC: mesmo da turma

**Resultado Esperado:**
- **Histórico:** Bloco "Exames OK" em verde
- **Modal:** Aluno aparece na lista
- **Debug Info:**
  - CFC da Turma: `<id>` (não undefined)
  - CFC da Sessão: `<id>` (não undefined)
  - CFCs coincidem: Sim/Não (baseado nos valores reais)

### ✅ Cenário 2: Aluno com Exames OK e Fatura Vencida

**Estado:**
- Exames: ambos aptos
- Financeiro: fatura vencida

**Resultado Esperado:**
- **Logs:** `financeiro_ok=false`
- **Modal:** Aluno não aparece

### ✅ Cenário 3: Aluno com Financeiro OK, mas Exames Pendentes

**Estado:**
- Exames: pendentes
- Financeiro: OK

**Resultado Esperado:**
- **Logs:** `exames_ok=false`
- **Modal:** Aluno não aparece

## Garantias

✅ **CFC da Turma:** Obtido corretamente do banco
✅ **CFC da Sessão:** Obtido do usuário logado
✅ **Debug Info:** Sempre inclui valores de CFC
✅ **Logs Detalhados:** Facilita identificação de problemas
✅ **Fallback:** Frontend usa constantes JavaScript se API não retornar

## Próximos Passos para Teste

1. Abrir modal "Matricular Alunos na Turma" na turma teórica
2. Verificar console do navegador para logs `[TURMAS TEORICAS API]`
3. Verificar painel amarelo de debug no modal:
   - CFC da Turma deve mostrar número (não undefined)
   - CFC da Sessão deve mostrar número (não undefined)
   - CFCs coincidem deve mostrar Sim/Não baseado nos valores
4. Verificar se aluno 167 aparece na lista (se exames e financeiro OK)
5. Verificar logs do servidor (error_log) para detalhes completos

