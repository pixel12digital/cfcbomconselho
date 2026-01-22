# 📋 RESUMO TÉCNICO: Status de Aula Teórica - Divergência Dashboard vs Painel

**Data:** 2025-01-XX  
**Problema:** Aula teórica aparece como "CONCLUÍDA" no painel do instrutor, mas continua contando como "pendente" no "Resumo de Hoje"

---

## 🔍 DIAGNÓSTICO TÉCNICO

### 1. FONTE DA VERDADE DO STATUS

**Tabela/Campo:** `turma_aulas_agendadas.status`

**Valores possíveis (ENUM):**
- `'agendada'` - Aula agendada, ainda não realizada
- `'realizada'` - Aula realizada (chamada feita)
- `'cancelada'` - Aula cancelada
- `'reagendada'` - Aula reagendada

**Arquivo de definição:**
- `admin/migrations/001-create-turmas-teoricas-structure.sql` (linha 145)
- `admin/includes/TurmaTeoricaManager.php` (linha 282)

---

### 2. FLUXO AO SALVAR CHAMADA

**Arquivo:** `admin/pages/turma-chamada.php`

**Botão "Salvar Chamada":**
- **Linha 806-808:** Botão HTML que chama `salvarChamada()`
- **Linha 1529-1533:** Função JavaScript `salvarChamada()` - **APENAS PLACEHOLDER**
  ```javascript
  function salvarChamada() {
      mostrarToast('Chamada salva automaticamente!');
      alteracoesPendentes = false;
  }
  ```
- **Problema:** A função não faz nada, apenas mostra um toast. Não atualiza o status da aula.

**Tabelas atualizadas ao marcar presenças:**
- `turma_presencas` - Registro de presenças individuais (via API `admin/api/turma-presencas.php`)
- `turma_aulas_agendadas.status` - **Atualizado apenas quando a primeira presença é registrada**

**Arquivo da API:** `admin/api/turma-presencas.php`

**Atualização de status (linhas 666-688 e 845-871):**
```php
// Atualizar status da aula para 'realizada' se for a primeira presença registrada
$totalPresencas = $db->fetch(
    "SELECT COUNT(*) as total FROM turma_presencas WHERE turma_id = ? AND turma_aula_id = ?",
    [$dados['turma_id'], $turmaAulaId]
);

// Se é a primeira presença da aula, atualizar status
if (($totalPresencas['total'] ?? 0) == 1) {
    $aulaAtual = $db->fetch(
        "SELECT status FROM turma_aulas_agendadas WHERE id = ? AND turma_id = ?",
        [$turmaAulaId, $dados['turma_id']]
    );
    
    // Só atualiza se ainda estiver 'agendada' (evita sobrescrever se já foi atualizada)
    if ($aulaAtual && ($aulaAtual['status'] ?? '') === 'agendada') {
        $db->update('turma_aulas_agendadas', 
            ['status' => 'realizada'],
            'id = ? AND turma_id = ?',
            [$turmaAulaId, $dados['turma_id']]
        );
    }
}
```

**Problema identificado:**
- O status só é atualizado para `'realizada'` quando a **primeira presença é registrada**
- Se o instrutor salvar a chamada sem marcar nenhum aluno (todos ausentes), o status permanece `'agendada'`
- O dashboard conta `'agendada'` como pendente

---

### 3. CÁLCULO DOS CONTADORES NO DASHBOARD

**Arquivo:** `instrutor/dashboard.php`

**Linhas 377-422:** Cálculo dos contadores "Pendentes" e "Concluídas"

**Query/Regra para teórica:**
```php
foreach ($aulasHoje as $aula) {
    if ($aula['tipo_aula'] === 'teorica') {
        // Aula teórica: considerar status do banco
        $status = $aula['status'] ?? '';
        // Concluída se status = 'realizada' (independente de chamada_registrada)
        if ($status === 'realizada') {
            $concluidas++;
        } elseif ($status !== 'cancelada') {
            // Pendente: qualquer outro status que não seja 'realizada' nem 'cancelada'
            // (inclui 'agendada' e outros estados possíveis)
            $pendentes++;
        }
        // 'cancelada' não conta em nenhum dos dois
    }
}
```

**Status que entram em cada contador:**
- **Pendentes:** `'agendada'` e qualquer outro status que não seja `'realizada'` nem `'cancelada'`
- **Concluídas:** Apenas `'realizada'`
- **Não contam:** `'cancelada'`

---

### 4. MOTIVO DA DIVERGÊNCIA

**Cenário do problema:**
1. Instrutor abre a chamada de uma aula teórica
2. Marca todos os alunos como ausentes (ou não marca nenhum)
3. Clica em "Salvar Chamada"
4. A função `salvarChamada()` não faz nada (apenas mostra toast)
5. O status da aula permanece `'agendada'` (não muda para `'realizada'`)
6. O dashboard conta `'agendada'` como pendente
7. Mas no painel, a aula pode aparecer como "CONCLUÍDA" se houver alguma presença registrada anteriormente (que atualizou o status)

**Causa raiz:**
- A função `salvarChamada()` é um placeholder que não atualiza o status
- O status só é atualizado automaticamente quando a primeira presença é registrada
- Se não houver presenças, o status não muda

---

## 📍 ARQUIVOS/LINHAS ONDE ISSO ACONTECE

### Arquivos principais:

1. **`admin/pages/turma-chamada.php`**
   - Linha 806-808: Botão "Salvar Chamada"
   - Linha 1529-1533: Função `salvarChamada()` (placeholder)

2. **`admin/api/turma-presencas.php`**
   - Linhas 666-688: Atualização de status ao criar presença individual
   - Linhas 845-871: Atualização de status ao criar presenças em lote

3. **`instrutor/dashboard.php`**
   - Linhas 377-422: Cálculo dos contadores "Pendentes" e "Concluídas"
   - Linha 406: Verificação `if ($status === 'realizada')` para contar como concluída
   - Linha 409: Verificação para contar como pendente (qualquer status que não seja `'realizada'` nem `'cancelada'`)

---

## ✅ SOLUÇÃO PROPOSTA

**Opção escolhida:** Opção A (preferida)

**Implementação:**
1. Modificar a função `salvarChamada()` para chamar uma API que atualiza o status da aula para `'realizada'`
2. Criar endpoint na API `turma-presencas.php` ou usar endpoint existente
3. Garantir que o status seja atualizado mesmo sem presenças (idempotente)

**Critérios de aceite:**
- ✅ Ao clicar em "Salvar Chamada", o status muda para `'realizada'` (mesmo sem presenças)
- ✅ Ao voltar no dashboard, "Pendentes" diminui e "Concluídas" aumenta
- ✅ Funciona mesmo com 0 presentes (todos ausentes)
- ✅ Re-salvar a chamada não quebra nada (idempotência)
- ✅ Não afeta o fluxo de aula prática

---

**Status:** ✅ Diagnóstico completo - Pronto para implementação
