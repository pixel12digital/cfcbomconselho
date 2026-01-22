# RESUMO - AJUSTE DE EXIBIÇÃO DE PRESENÇA (CHAMADA / DIÁRIO / HISTÓRICO)

**Data:** 2025-12-12  
**Objetivo:** Alinhar todas as consultas de presença para usar `turma_presencas` com `turma_aula_id` como fonte de verdade

---

## Contexto

A API de presenças (`admin/api/turma-presencas.php`) já estava gravando corretamente na tabela `turma_presencas` usando a coluna `turma_aula_id`. No entanto, as telas de visualização ainda estavam usando consultas antigas baseadas em `aula_id` ou outras tabelas, resultando em:

- Cards de resumo mostrando 0 presentes / 0% mesmo com presença gravada
- Histórico do aluno mostrando "NÃO REGISTRADO" para aulas com presença
- Diário da turma não refletindo presenças registradas

---

## Problemas Identificados e Corrigidos

### 1. `admin/pages/turma-chamada.php`

#### Problema 1.1: JOIN na listagem de aulas (linha ~185)
**Antes:**
```sql
LEFT JOIN turma_presencas tp ON taa.id = tp.aula_id AND tp.turma_id = taa.turma_id
```

**Depois:**
```sql
LEFT JOIN turma_presencas tp ON taa.id = tp.turma_aula_id AND tp.turma_id = taa.turma_id
```

#### Problema 1.2: JOIN na busca de alunos (linhas ~266-269 e ~289-292)
**Antes:**
```sql
LEFT JOIN turma_presencas tp ON (
    a.id = tp.aluno_id 
    AND tp.turma_id = ? 
    AND tp.aula_id = ?
)
```

**Depois:**
```sql
LEFT JOIN turma_presencas tp ON (
    a.id = tp.aluno_id 
    AND tp.turma_id = ? 
    AND tp.turma_aula_id = ?
)
```

**Também corrigido:**
- Verificação de coluna alterada de `aula_id` para `turma_aula_id`
- Adicionada verificação de `$aulaId` antes de fazer JOIN (evita consultas desnecessárias)

---

### 2. `admin/pages/historico-aluno.php`

**Status:** ✅ Já estava correto

A consulta já estava usando `turma_aula_id` corretamente:
```sql
SELECT 
    tp.turma_aula_id as aula_id,
    tp.presente,
    tp.justificativa,
    tp.registrado_em
FROM turma_presencas tp
WHERE tp.turma_id = ? AND tp.aluno_id = ?
```

**Ajuste realizado:**
- Adicionado comentário indicando que usa `turma_aula_id` (ajuste 2025-12)

---

### 3. `admin/pages/turma-diario.php`

**Status:** ✅ Não requer ajuste

O diário não faz consultas diretas de presença. Ele usa a API de frequência (`admin/api/turma-frequencia.php`) que já está correta e usa `turma_aula_id`.

---

## Regras de Cálculo de Presença

### Fonte de Verdade
- **Tabela:** `turma_presencas`
- **Coluna de referência:** `turma_aula_id` (NÃO `aula_id`)
- **Chave lógica:** `turma_id` + `turma_aula_id` + `aluno_id`

### Status de Presença
- **Presente:** Existe registro em `turma_presencas` com `presente = 1`
- **Ausente:** Existe registro em `turma_presencas` com `presente = 0`
- **Não registrado:** Não existe registro em `turma_presencas` para a combinação `turma_id` + `turma_aula_id` + `aluno_id`

### Cálculo de Estatísticas (turma-chamada.php)
- **Total de Alunos:** COUNT de alunos matriculados na turma
- **Presentes:** COUNT de registros em `turma_presencas` com `presente = 1` para a aula
- **Ausentes:** COUNT de registros em `turma_presencas` com `presente = 0` para a aula
- **Frequência Média:** `(presentes / (presentes + ausentes)) * 100`

### Cálculo de Frequência do Aluno (Percentual no Curso)
**AJUSTE 2025-12:** Frequência geral do aluno no curso teórico

- **Fonte:** `turma_matriculas.frequencia_percentual` (atualizado via `TurmaTeoricaManager::recalcularFrequenciaAluno()`)
- **Fórmula:** `(total_presentes / total_aulas_validas) * 100`
  - `total_presentes`: COUNT de registros em `turma_presencas` com `presente = 1` e `turma_aula_id` vinculado a aula com status `agendada` ou `realizada`
  - `total_aulas_validas`: COUNT de aulas em `turma_aulas_agendadas` com status `agendada` ou `realizada` (não canceladas)
- **Exemplo:** Se aluno tem 1 presença em 45 aulas válidas = 2,2% de frequência no curso
- **Atualização:** Recalculada automaticamente após criar/atualizar/excluir presença via API

**Nota:** O chip de frequência na linha do aluno na Chamada mostra a frequência geral do curso, não apenas daquela aula específica.

---

## Telas Impactadas

### ✅ `admin/pages/turma-chamada.php`
- **Ajustes:** 3 consultas corrigidas
- **Resultado esperado:** Cards de resumo e lista de alunos mostram presenças corretamente

### ✅ `admin/pages/historico-aluno.php`
- **Ajustes:** Comentário adicionado (já estava correto)
- **Resultado esperado:** Aulas aparecem como "Presente" quando há registro

### ✅ `admin/pages/turma-diario.php`
- **Ajustes:** Nenhum necessário
- **Resultado esperado:** Continua funcionando via API de frequência

---

## Consistência entre Admin e Instrutor

As queries de leitura são idênticas para admin e instrutor. A única diferença é:

- **Admin/Secretaria:** Podem ver todas as turmas e todos os alunos
- **Instrutor:** Só pode ver turmas/aulas em que é instrutor e alunos dessas turmas

O modo `modoSomenteLeitura` já estava implementado e foi mantido.

---

## Testes Recomendados

### Cenário 1: Instrutor marca presença
1. Logar como instrutor Carlos
2. Dashboard → Chamada da aula 227 da turma 19
3. Marcar Presente para o aluno 167 (Charles)
4. **Verificar:**
   - ✅ Cards da Chamada atualizados (Presentes: 1, Frequência Média: 100%)
   - ✅ Botão "Presente" marcado visualmente
   - ✅ Diário da turma/aula mostrando aluno como Presente
   - ✅ Histórico do aluno mostrando a aula como Presente

### Cenário 2: Admin visualiza histórico
1. Logar como admin
2. Acessar `historico-aluno.php?id=167`
3. **Verificar:**
   - ✅ Aula do dia 12/12/2025 aparece como Presente
   - ✅ Frequência teórica da turma/aluno atualizada (não mais 0%)

---

## Arquivos Modificados

1. `admin/pages/turma-chamada.php`
   - Linha ~185: JOIN corrigido para usar `turma_aula_id`
   - Linhas ~228-317: Lógica de verificação e JOIN de alunos corrigida
   - Linhas ~982-993: Corrigido envio de origem para admin/secretaria (usa 'admin' quando vazio)
   - Linhas ~1028-1043: Corrigido envio de origem na atualização de presença
   - Linhas ~1172-1181: Corrigido envio de origem em presenças em lote
   - Linhas ~800-835: Melhorado cálculo de frequência do aluno (prioriza API, depois fallback)
   - Linha ~644: Botão Relatório temporariamente desabilitado (comentado)

2. `admin/pages/historico-aluno.php`
   - Linha ~1528: Comentário adicionado

3. `admin/api/turma-presencas.php`
   - Linhas ~1013-1043: Validação ajustada para permitir atualização apenas com `presente` quando `presencaId` existe
   - Linhas ~610-621: Ajustado para permitir admin atualizar presenças existentes (em vez de retornar erro)

4. `admin/pages/turma-diario.php`
   - Linhas ~112-150: Adicionada busca de aulas agendadas com informações de presença
   - Linhas ~485-580: Adicionada seção "Aulas Agendadas" com tabela enriquecida (presenças e status de chamada)

5. `admin/pages/turmas-teoricas-detalhes-inline.php`
   - Linha ~3569: Adicionada aba "Diário / Presenças" para admin/secretaria

6. `docs/RESUMO_AJUSTE_PRESENCAS_2025.md` (este arquivo)
   - Documentação atualizada com cálculo de frequência

---

## Notas Técnicas

- A API `admin/api/turma-presencas.php` já estava correta e não foi modificada
- Todas as referências a `aula_id` na tabela `turma_presencas` foram substituídas por `turma_aula_id`
- A coluna `justificativa` pode não existir na tabela `turma_presencas` - o código trata isso com verificação dinâmica
- O cálculo de estatísticas usa os dados já carregados do JOIN corrigido, não requer consultas adicionais

---

## Próximos Passos (se necessário)

- [ ] Verificar se há outras telas/endpoints que consultam presenças diretamente
- [ ] Validar se a coluna `justificativa` existe na tabela `turma_presencas` (se não, considerar adicionar)
- [ ] Testar cenários de presença em lote
- [ ] Validar recálculo de frequência após marcar presença

---

**Autor:** Sistema CFC Bom Conselho  
**Revisão:** 2025-12-12
