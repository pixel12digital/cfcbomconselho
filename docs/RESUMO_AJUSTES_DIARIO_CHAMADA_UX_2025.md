# RESUMO - AJUSTES DIÁRIO / CHAMADA / UX (ADMIN & SECRETARIA)

**Data:** 2025-12-12  
**Objetivo:** Corrigir bugs de presença, alinhar cálculos de frequência, melhorar UX e remover elementos quebrados

---

## Contexto

Após implementar o fluxo básico de acesso ao Diário e Chamada, foram identificados problemas que impediam admin/secretaria de editar presenças e exibiam informações inconsistentes de frequência.

---

## Bugs Corrigidos

### 1. Bug: Admin não conseguia marcar Presente/Ausente (toast "Dados inválidos")

#### Problema
Quando admin (sem `origem=instrutor`) tentava marcar presença na tela de chamada, aparecia toast "Dados inválidos".

#### Causa Raiz
1. **Variável `origem` vazia:** Quando admin acessa sem `origem=instrutor`, a variável `ORIGEM_FLUXO` no JS ficava como string vazia `""`
2. **Validação da API:** A função `validarDadosPresenca()` exigia todos os campos mesmo para atualização
3. **Presença existente:** Quando admin tentava criar presença que já existia (marcada pelo instrutor), a API retornava erro em vez de permitir atualizar

#### Correções Implementadas

**1.1. `admin/pages/turma-chamada.php` - Função `criarPresenca()`:**
```javascript
// ANTES:
if (ORIGEM_FLUXO) {
    dados.origem = ORIGEM_FLUXO;
}

// DEPOIS:
const origemParaEnvio = (ORIGEM_FLUXO && ORIGEM_FLUXO.trim() !== '') ? ORIGEM_FLUXO : 'admin';
dados.origem = origemParaEnvio;
```

**1.2. `admin/pages/turma-chamada.php` - Função `atualizarPresenca()`:**
```javascript
// ANTES:
if (ORIGEM_FLUXO) {
    dados.origem = ORIGEM_FLUXO;
}

// DEPOIS:
const origemParaEnvio = (ORIGEM_FLUXO && ORIGEM_FLUXO.trim() !== '') ? ORIGEM_FLUXO : 'admin';
dados.origem = origemParaEnvio;
```

**1.3. `admin/api/turma-presencas.php` - Função `validarDadosPresenca()`:**
```php
// AJUSTE: Para atualização (quando presencaId existe), apenas presente é obrigatório
if ($presencaId !== null) {
    if (!isset($dados['presente'])) {
        $erros[] = 'Status de presença é obrigatório';
    }
    // ... retorna sucesso se apenas presente estiver presente
}
```

**1.4. `admin/api/turma-presencas.php` - Função `marcarPresencaIndividual()`:**
```php
// ANTES:
if ($presencaExistente) {
    return ['success' => false, 'message' => 'Presença já registrada...'];
}

// DEPOIS:
if ($presencaExistente) {
    // Se já existe, atualizar em vez de criar nova
    return atualizarPresenca($db, $presencaExistente['id'], $dados, $userId);
}
```

#### Resultado
✅ Admin/secretaria podem agora:
- Criar novas presenças
- Atualizar presenças existentes (marcadas pelo instrutor)
- Trocar Presente → Ausente e vice-versa sem erros

---

### 2. Bug: Frequência 100% no card e 0,0% no chip do aluno

#### Problema
- Card de topo mostrava "Frequência Média: 100%" (correto para aquela aula)
- Chip do aluno mostrava "0,0%" (incorreto)

#### Causa Raiz
O chip do aluno estava usando `frequencia_percentual` da tabela `turma_matriculas`, que:
- Pode não estar sendo recalculada corretamente
- Pode estar zerada se o aluno acabou de ser matriculado
- Não estava sendo atualizada em tempo real na interface

#### Correções Implementadas

**2.1. `admin/pages/turma-chamada.php` - Cálculo de frequência do aluno:**
```php
// ANTES: Priorizava frequencia_percentual direto do aluno
if (isset($aluno['frequencia_percentual']) && $aluno['frequencia_percentual'] !== null) {
    $percentualFreq = (float)$aluno['frequencia_percentual'];
}

// DEPOIS: Prioriza API de frequência, depois fallback
// 1. Primeiro: API de frequência (mais confiável e atualizado)
if ($frequenciaGeral && isset($frequenciaGeral['frequencias_alunos'])) {
    foreach ($frequenciaGeral['frequencias_alunos'] as $freq) {
        if ($freq['aluno']['id'] == $aluno['id']) {
            $percentualFreq = (float)$freq['estatisticas']['percentual_frequencia'];
            break;
        }
    }
}
// 2. Segundo: frequencia_percentual da matrícula (fallback)
// 3. Terceiro: Cálculo direto das presenças (último recurso)
```

**2.2. Regra de cálculo de frequência:**
- **Frequência do aluno no curso:** `(total_presentes / total_aulas_validas) * 100`
  - `total_presentes`: COUNT de presenças com `presente = 1` em aulas válidas
  - `total_aulas_validas`: COUNT de aulas com status `agendada` ou `realizada` (não canceladas)
- **Exemplo:** 1 presença em 45 aulas = 2,2% (não 100%)

#### Resultado
✅ Chip do aluno agora mostra frequência correta do curso
✅ Frequência é atualizada automaticamente após marcar presença (via `atualizarFrequenciaAluno()`)

---

### 3. Botão "Relatório" retornando 404

#### Problema
Botão "Relatório" na tela de Chamada apontava para `turma-relatorios.php` que não existe.

#### Solução Implementada
**Opção A - Esconder temporariamente:**
- Botão foi comentado no código
- TODO adicionado indicando que precisa ser implementado ou removido permanentemente

**Localização:** `admin/pages/turma-chamada.php` linha ~644

#### Resultado
✅ Nenhum 404 ao clicar em Relatório (botão não aparece)

---

## Melhorias de UX

### 4.1. Aba "Diário / Presenças" na tela de detalhes da turma

#### Implementação
**Arquivo:** `admin/pages/turmas-teoricas-detalhes-inline.php`

**Código adicionado:**
```php
<?php if ($isAdmin || hasPermission('secretaria')): ?>
<!-- AJUSTE 2025-12 - Aba Diário / Presenças para admin/secretaria -->
<button class="tab-button" onclick="window.location.href='?page=turma-diario&turma_id=<?= $turmaId ?>'">
    <i class="fas fa-book-open"></i>
    <span>Diário / Presenças</span>
</button>
<?php endif; ?>
```

**Localização:** Após a aba "Estatísticas" (linha ~3569)

#### Resultado
✅ Admin/secretaria têm dois pontos de acesso ao Diário:
1. Card da turma → Menu (⋮) → "Ver Diário"
2. Gerenciar turma → Aba "Diário / Presenças"

---

### 4.2. Tabela "Aulas Agendadas" enriquecida

#### Implementação
**Arquivo:** `admin/pages/turma-diario.php`

**Colunas adicionadas:**
1. **"Presenças":** Mostra `X/Y` (presentes/total de alunos) e quantidade de ausentes
2. **"Chamada":** Status da chamada:
   - "Não iniciada" (nenhuma presença registrada)
   - "Em andamento" (presenças parciais)
   - "Concluída" (todos os alunos com presença registrada)

**Consulta enriquecida:**
```sql
SELECT 
    taa.id,
    ...
    COUNT(DISTINCT CASE WHEN tp.presente = 1 THEN tp.id END) as total_presentes,
    COUNT(DISTINCT CASE WHEN tp.presente = 0 THEN tp.id END) as total_ausentes,
    COUNT(DISTINCT tp.id) as total_registrados
FROM turma_aulas_agendadas taa
LEFT JOIN turma_presencas tp ON (
    tp.turma_aula_id = taa.id 
    AND tp.turma_id = taa.turma_id
)
WHERE taa.turma_id = ?
GROUP BY ...
```

#### Resultado
✅ Admin tem visão rápida do status de cada aula:
- Quantos alunos estão presentes/ausentes
- Se a chamada foi iniciada, está em andamento ou concluída

---

## Arquivos Modificados

1. **`admin/pages/turma-chamada.php`**
   - Linhas ~982-993: Corrigido envio de origem em `criarPresenca()`
   - Linhas ~1028-1043: Corrigido envio de origem em `atualizarPresenca()`
   - Linhas ~1172-1181: Corrigido envio de origem em `marcarTodos()`
   - Linhas ~800-835: Melhorado cálculo de frequência do aluno
   - Linha ~644: Botão Relatório desabilitado

2. **`admin/api/turma-presencas.php`**
   - Linhas ~1013-1043: Validação ajustada para atualização
   - Linhas ~610-621: Permite admin atualizar presenças existentes

3. **`admin/pages/turma-diario.php`**
   - Linhas ~112-150: Busca de aulas enriquecida com presenças
   - Linhas ~505-545: Tabela com colunas "Presenças" e "Chamada"

4. **`admin/pages/turmas-teoricas-detalhes-inline.php`**
   - Linha ~3569: Aba "Diário / Presenças" adicionada

5. **`docs/RESUMO_AJUSTE_PRESENCAS_2025.md`**
   - Seção de cálculo de frequência atualizada

6. **`docs/RESUMO_FLUXO_ADMIN_DIARIO_CHAMADA_2025.md`**
   - Informações sobre novos acessos e capacidades atualizadas

7. **`docs/RESUMO_AJUSTES_DIARIO_CHAMADA_UX_2025.md`** (este arquivo)
   - Documentação criada

---

## Testes Realizados

### ✅ Cenário 1: Instrutor marca presença, admin corrige
**Passos:**
1. Instrutor Carlos marca Charles como Presente na aula 227 (fluxo instrutor)
2. Admin acessa: Turmas Teóricas → Ver Diário → Chamada dessa aula
3. Admin troca Presente → Ausente
4. Admin troca Ausente → Presente

**Resultado:**
- ✅ Nenhum erro "Dados inválidos"
- ✅ Toasts exibem "Presença atualizada com sucesso"
- ✅ Cards de topo atualizam corretamente
- ✅ Frequência do aluno atualiza (não fica em 0%)

### ✅ Cenário 2: Frequência em mais de uma aula
**Passos:**
1. Criar mais uma aula para a turma
2. Registrar presença/ausência para o Charles
3. Verificar frequência geral

**Resultado:**
- ✅ Frequência geral do curso para o aluno faz sentido (ex: 2/45 = 4,4%)
- ✅ Histórico do aluno reflete corretamente

### ✅ Cenário 3: Acesso ao Diário
**Passos:**
1. Acesso pelo card (⋮ → Ver Diário)
2. Acesso pela aba "Diário / Presenças" na tela de detalhes

**Resultado:**
- ✅ Ambos levam à mesma tela
- ✅ Tabela "Aulas Agendadas" mostra informações de presença

### ✅ Cenário 4: Botão Relatório
**Resultado:**
- ✅ Botão não aparece (comentado)
- ✅ Nenhum 404

---

## Limitações Conhecidas

1. **Botão Relatório:** Ainda não implementado. Pode ser:
   - Removido permanentemente se não for necessário
   - Implementado como página simples de impressão
   - Implementado como relatório PDF completo (futuro)

2. **Frequência em tempo real:** A frequência do aluno é atualizada via AJAX após marcar presença, mas pode haver um pequeno delay. Se necessário, pode ser melhorado com polling ou WebSocket.

3. **Status da chamada:** O status "Em andamento" considera apenas se todos os alunos têm presença registrada. Não diferencia se há alunos ausentes vs. não registrados.

---

## Próximos Passos (Opcional)

- [ ] Implementar página de relatórios simples (HTML com CSS de impressão)
- [ ] Adicionar filtros na tabela de aulas (por data, disciplina, status de chamada)
- [ ] Melhorar atualização de frequência em tempo real (WebSocket ou polling)
- [ ] Adicionar exportação de presenças (CSV/Excel)

---

**Autor:** Sistema CFC Bom Conselho  
**Revisão:** 2025-12-12
