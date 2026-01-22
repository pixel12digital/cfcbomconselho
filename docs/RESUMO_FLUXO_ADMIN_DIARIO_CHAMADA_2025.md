# RESUMO - FLUXO ADMIN/SECRETARIA PARA DIÁRIO E CHAMADA

**Data:** 2025-12-12  
**Objetivo:** Adicionar fluxo claro para admin/secretaria acessar Diário e Chamada de turmas teóricas

---

## Contexto

Após corrigir as consultas de presença para usar `turma_aula_id`, era necessário criar um fluxo claro para admin/secretaria acessar as telas de Diário e Chamada a partir da lista de turmas teóricas, sem usar `origem=instrutor`.

---

## Mudanças Implementadas

### 1. `admin/pages/turmas-teoricas-lista.php`

#### Adicionado: Link "Ver Diário" no menu de ações
**Localização:** Menu dropdown de cada card de turma (linha ~189)

**Código adicionado:**
```php
<?php if ($isAdmin || hasPermission('secretaria')): ?>
    <!-- AJUSTE 2025-12 - Link para Diário da Turma (admin/secretaria) -->
    <a href="?page=turma-diario&turma_id=<?= $turma['id'] ?>" onclick="closeTurmaCardMenusImmediate()">
        <i class="fas fa-book-open"></i>
        Ver Diário
    </a>
<?php endif; ?>
```

**Características:**
- Aparece apenas para admin/secretaria (não para instrutores)
- Não usa `origem=instrutor`
- Fecha o menu dropdown ao clicar

---

### 2. `admin/pages/turma-diario.php`

#### Adicionado: Seção "Aulas Agendadas" com link para Chamada
**Localização:** Nova seção após "Alunos Matriculados"

**Funcionalidades:**
1. **Busca de aulas agendadas:**
   ```php
   // AJUSTE 2025-12 - Buscar aulas agendadas da turma para exibir no diário
   $aulasAgendadas = $db->fetchAll("
       SELECT 
           taa.id,
           taa.nome_aula,
           taa.disciplina,
           taa.data_aula,
           taa.hora_inicio,
           taa.hora_fim,
           taa.status as aula_status,
           taa.ordem_global,
           i.nome as instrutor_nome
       FROM turma_aulas_agendadas taa
       LEFT JOIN instrutores i ON taa.instrutor_id = i.id
       WHERE taa.turma_id = ?
       ORDER BY taa.ordem_global ASC, taa.data_aula ASC, taa.hora_inicio ASC
   ", [$turmaId]);
   ```

2. **Tabela de aulas enriquecida com:**
   - Data da aula
   - Horário (início - fim)
   - Nome da aula
   - Disciplina (com nomes formatados)
   - Instrutor
   - Status da aula
   - **Coluna "Presenças":** Mostra X/Y (presentes/total de alunos) e quantidade de ausentes
   - **Coluna "Chamada":** Status da chamada:
     - "Não iniciada" (nenhuma presença registrada)
     - "Em andamento" (presenças parciais)
     - "Concluída" (todos os alunos com presença registrada)
   - **Botão "Chamada"** que leva para `?page=turma-chamada&turma_id={ID}&aula_id={TURMA_AULA_ID}`

3. **Lógica de origem:**
   - Se `origem !== 'instrutor'`: Link sem `origem=instrutor` (admin/secretaria)
   - Se `origem === 'instrutor'`: Link com `origem=instrutor` (mantém fluxo do instrutor)

---

## Fluxo Completo

### Para Admin/Secretaria:

1. **Acesso inicial:**
   - Menu lateral → Acadêmico → Turmas Teóricas
   - URL: `admin/index.php?page=turmas-teoricas`

2. **Lista de turmas:**
   - Visualiza cards das turmas teóricas
   - Cada card tem menu de ações (3 pontinhos)

3. **Acesso ao Diário:**
   - **Opção A:** Clica no menu de ações do card da turma → Seleciona "Ver Diário"
   - **Opção B:** Clica em "Gerenciar turma" → Aba "Diário / Presenças"
   - URL: `admin/index.php?page=turma-diario&turma_id={ID}`

4. **Acesso à Chamada:**
   - No Diário, visualiza seção "Aulas Agendadas"
   - Clica no botão "Chamada" da aula desejada
   - URL: `admin/index.php?page=turma-chamada&turma_id={ID}&aula_id={TURMA_AULA_ID}`

5. **Ajuste de presença:**
   - Na tela de Chamada, marca/desmarca presença dos alunos
   - **AJUSTE 2025-12:** Admin/secretaria podem editar presenças já registradas pelo instrutor
   - Cards de resumo atualizam automaticamente
   - Frequência do aluno é recalculada e atualizada na interface
   - Pode voltar ao Diário e verificar status atualizado na coluna "Presenças"

---

## Validações e Permissões

### Verificação de Permissão para "Ver Diário"
```php
<?php if ($isAdmin || hasPermission('secretaria')): ?>
```

- **Admin:** Sempre pode ver
- **Secretaria:** Pode ver (se tiver permissão)
- **Instrutor:** Não aparece no menu (usa fluxo próprio com `origem=instrutor`)

### Lógica de Origem no Diário
```php
<?php if ($origem !== 'instrutor'): ?>
    <!-- Link sem origem=instrutor -->
<?php else: ?>
    <!-- Link com origem=instrutor -->
<?php endif; ?>
```

- **Admin/Secretaria:** Acessam sem `origem=instrutor`
- **Instrutor:** Mantém `origem=instrutor` se chegar via dashboard

---

## Consistência com Correções Anteriores

### Uso de `turma_aula_id`
- ✅ O diário não faz consultas diretas de presença (usa API de frequência)
- ✅ A tela de chamada já foi corrigida para usar `turma_aula_id`
- ✅ O histórico do aluno já usa `turma_aula_id` corretamente

### Não quebra fluxo do instrutor
- ✅ Links do instrutor continuam usando `origem=instrutor`
- ✅ Permissões do instrutor são mantidas
- ✅ Dashboard do instrutor não foi alterado

---

## Arquivos Modificados

1. **`admin/pages/turmas-teoricas-lista.php`**
   - Linha ~189: Adicionado link "Ver Diário" no menu dropdown

2. **`admin/pages/turma-diario.php`**
   - Linhas ~112-150: Adicionada busca de aulas agendadas com informações de presença (total_presentes, total_ausentes, status_chamada)
   - Linhas ~485-580: Adicionada seção "Aulas Agendadas" com tabela enriquecida:
     - Coluna "Presenças": mostra X/Y (presentes/total de alunos)
     - Coluna "Chamada": mostra status (Não iniciada / Em andamento / Concluída)
     - Botão "Chamada" para cada aula

3. **`admin/pages/turmas-teoricas-detalhes-inline.php`**
   - Linha ~3569: Adicionada aba "Diário / Presenças" para admin/secretaria (redireciona para turma-diario)

4. **`admin/pages/turma-chamada.php`**
   - Linhas ~982-993: Corrigido envio de origem para admin/secretaria (usa 'admin' quando vazio)
   - Linhas ~1028-1043: Corrigido envio de origem na atualização de presença
   - Linhas ~800-835: Melhorado cálculo de frequência do aluno (prioriza API, depois fallback)
   - Linha ~644: Botão Relatório temporariamente desabilitado (comentado)

5. **`admin/api/turma-presencas.php`**
   - Linhas ~1013-1043: Validação ajustada para permitir atualização apenas com `presente` quando `presencaId` existe
   - Linhas ~610-621: Ajustado para permitir admin atualizar presenças existentes (em vez de retornar erro)

6. **`docs/RESUMO_FLUXO_ADMIN_DIARIO_CHAMADA_2025.md`** (este arquivo)
   - Documentação atualizada

---

## Testes Recomendados

### Cenário 1: Admin acessa Diário e Chamada
1. Logar como administrador
2. Acessar `admin/index.php?page=turmas-teoricas`
3. Localizar turma "Turma A - Formação CNH AB" (turma_id = 19)
4. Clicar no menu de ações (3 pontinhos) do card
5. **Verificar:** Link "Ver Diário" aparece no menu
6. Clicar em "Ver Diário"
7. **Verificar:** Diário abre mostrando detalhes da turma e seção "Aulas Agendadas"
8. Na seção "Aulas Agendadas", localizar aula com turma_aula_id = 227
9. Clicar no botão "Chamada"
10. **Verificar:** Tela de Chamada abre sem erros
11. Marcar presença do aluno Charles (aluno_id = 167)
12. **Verificar:** Cards de resumo atualizam (Presentes: 1, Frequência Média: 100%)
13. Voltar ao Diário
14. **Verificar:** Aula continua listada corretamente

### Cenário 2: Secretaria acessa (se aplicável)
1. Logar como secretaria
2. Repetir passos 2-14 do Cenário 1
3. **Verificar:** Comportamento idêntico ao admin

### Cenário 3: Instrutor (não deve quebrar)
1. Logar como instrutor Carlos
2. Acessar dashboard do instrutor
3. Acessar chamada via dashboard
4. **Verificar:** Fluxo continua funcionando normalmente
5. Se chegar ao diário via `origem=instrutor`, verificar que links mantêm `origem=instrutor`

---

## Notas Técnicas

- O link "Ver Diário" só aparece para admin/secretaria, não para instrutores
- O diário detecta automaticamente se deve usar `origem=instrutor` ou não baseado no parâmetro da URL
- A seção "Aulas Agendadas" mostra todas as aulas da turma, ordenadas por ordem_global, data e horário
- Se não houver aulas agendadas, mostra mensagem informativa
- Os nomes das disciplinas são formatados para exibição amigável
- **AJUSTE 2025-12:** Admin/secretaria sempre enviam `origem='admin'` nas requisições de presença (mesmo quando não há origem na URL)
- **AJUSTE 2025-12:** API permite admin atualizar presenças existentes (em vez de retornar erro de duplicidade)
- **AJUSTE 2025-12:** Botão "Relatório" na Chamada foi temporariamente desabilitado (página não existe)
- **AJUSTE 2025-12:** Frequência do aluno é calculada como percentual do curso (presenças / total de aulas válidas), não apenas da aula atual

---

## Próximos Passos (se necessário)

- [ ] Validar se secretaria tem permissão `hasPermission('secretaria')` configurada corretamente
- [ ] Testar em ambiente de produção
- [ ] Considerar adicionar filtros na tabela de aulas (por data, disciplina, etc.)
- [ ] Considerar adicionar contador de presenças por aula na tabela

---

**Autor:** Sistema CFC Bom Conselho  
**Revisão:** 2025-12-12
