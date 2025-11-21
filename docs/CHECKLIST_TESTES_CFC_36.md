# Checklist de Testes - Alinhamento CFC Canônico (ID 36)

## ⚠️ IMPORTANTE

**Execute este checklist APENAS após:**
1. ✅ Revisar e executar as queries de diagnóstico em `docs/MIGRACAO_CFC_1_PARA_36.md`
2. ✅ Executar manualmente os UPDATEs de migração (CFC 1 → 36)
3. ✅ Confirmar que não restam registros com `cfc_id = 1` nas tabelas principais

---

## 1. Verificação Pré-Teste (Diagnóstico)

### 1.1. Executar Queries de Diagnóstico

Execute as queries de diagnóstico do arquivo `docs/MIGRACAO_CFC_1_PARA_36.md`:

```sql
-- Verificar distribuição de cfc_id em cada tabela
SELECT cfc_id, COUNT(*) AS total FROM alunos GROUP BY cfc_id;
SELECT cfc_id, COUNT(*) AS total FROM turmas_teoricas GROUP BY cfc_id;
SELECT cfc_id, COUNT(*) AS total FROM salas GROUP BY cfc_id;
SELECT cfc_id, COUNT(*) AS total FROM instrutores GROUP BY cfc_id;
SELECT cfc_id, COUNT(*) AS total FROM aulas GROUP BY cfc_id;
SELECT cfc_id, COUNT(*) AS total FROM veiculos GROUP BY cfc_id;
```

**Resultado Esperado:**
- ✅ Todas as tabelas devem mostrar apenas `cfc_id = 36` (ou nenhum registro se vazias)
- ❌ Não deve haver registros com `cfc_id = 1`

### 1.2. Verificar CFC Canônico

Acesse: `admin/tools/diagnostico-cfc-turma-16.php`

**Resultado Esperado:**
- ✅ Mostra claramente: "CFC Canônico do CFC Bom Conselho: 36"
- ✅ Turma 16 deve estar com `cfc_id = 36` (ou mostrar aviso se ainda estiver com 1)
- ✅ CFC ID 1 deve aparecer marcado como "⚠️ LEGADO (migrar para 36)"

### 1.3. Verificar Alunos

Acesse: `admin/tools/diagnostico-cfc-alunos.php?cfc_canonico=36`

**Resultado Esperado:**
- ✅ Mostra: "CFC Canônico do CFC Bom Conselho: 36"
- ✅ Todos os alunos ativos devem estar com `cfc_id = 36`
- ✅ Se houver alunos com `cfc_id = 1`, devem aparecer na lista de "Alunos com CFC Diferente"

---

## 2. Testes Funcionais

### 2.1. Histórico do Aluno 167 (Charles)

**Ação:**
1. Acesse: `admin/index.php?page=historico-aluno&id=167`

**Verificações:**
- [ ] Página carrega sem erros
- [ ] Exames médico e psicotécnico aparecem como "CONCLUÍDO" e "APTO"
- [ ] Bloqueios para Aulas Teóricas não mostram "Exames médico e psicotécnico não concluídos"
- [ ] Situação Financeira está OK (sem faturas vencidas)

**Resultado Esperado:**
- ✅ Todos os bloqueios relacionados a exames devem estar liberados
- ✅ Se houver bloqueio financeiro, deve ser apenas por faturas vencidas (não por falta de lançamentos)

---

### 2.2. Modal "Matricular Alunos na Turma" - Turma 16

**Ação:**
1. Acesse: `admin/index.php?page=turmas-teoricas&acao=detalhes&turma_id=16`
2. Clique no botão "Inserir Alunos" (ou equivalente)

**Verificações no Modal:**
- [ ] Modal abre sem erros
- [ ] Painel de Debug (amarelo) mostra:
  - `CFC da Turma: 36` (ou o CFC real da turma após migração)
  - `CFC da Sessão: 0 (admin_global)` ou `36 (cfc_especifico)`
  - `CFCs coincidem: N/A (Admin Global)` ou `Sim`
  - `Total candidatos: >= 1` (deve ser maior que 0)
  - `Total aptos: >= 1` (deve ser maior que 0 se houver alunos com exames/financeiro OK)

**Verificações na Lista de Alunos:**
- [ ] Aluno 167 (Charles) aparece na lista de candidatos aptos
- [ ] Se não aparecer, verificar logs do servidor para diagnóstico

**Resultado Esperado:**
- ✅ Aluno 167 deve aparecer como elegível
- ✅ Total de candidatos > 0
- ✅ Total de aptos >= 1 (se houver alunos com exames e financeiro OK)

---

### 2.3. Criar Novo Aluno

**Ação:**
1. Acesse: `admin/index.php?page=alunos`
2. Clique em "Novo Aluno"
3. Preencha os campos obrigatórios (nome, CPF, etc.)
4. **NÃO preencha o campo CFC** (ou deixe vazio se for Admin Global)
5. Salve o aluno

**Verificações:**
- [ ] Aluno é criado com sucesso
- [ ] Verificar no banco: `SELECT id, nome, cfc_id FROM alunos WHERE id = {novo_id}`

**Resultado Esperado:**
- ✅ Se usuário NÃO for Admin Global: `cfc_id` deve ser automaticamente o CFC da sessão (36)
- ✅ Se usuário for Admin Global: deve exigir seleção explícita do CFC (não pode ser vazio)
- ✅ Novo aluno deve ter `cfc_id = 36` (ou o CFC selecionado se Admin Global)

---

### 2.4. Novo Aluno com Exames e Financeiro OK

**Ação:**
1. Crie um novo aluno (conforme teste 2.3)
2. Agende e lance resultado dos exames médico e psicotécnico (ambos "Apto")
3. Crie uma fatura para o aluno e marque como paga (ou garanta que não há faturas vencidas)
4. Acesse o modal "Matricular Alunos na Turma" para a turma 16

**Verificações:**
- [ ] Novo aluno aparece na lista de candidatos aptos
- [ ] Aluno pode ser matriculado na turma

**Resultado Esperado:**
- ✅ Novo aluno aparece normalmente no modal de turmas teóricas
- ✅ Pode ser matriculado sem problemas

---

### 2.5. Editar Aluno Existente

**Ação:**
1. Edite um aluno existente (ex: aluno 167)
2. Tente alterar o campo CFC (se visível)
3. Salve

**Verificações:**
- [ ] Se usuário NÃO for Admin Global: campo CFC não deve permitir alteração (ou deve ser readonly)
- [ ] Se usuário for Admin Global: deve permitir alteração, mas exigir seleção explícita

**Resultado Esperado:**
- ✅ Usuário de CFC específico não consegue alterar `cfc_id` do aluno para um diferente do seu CFC
- ✅ Admin Global pode alterar, mas deve selecionar explicitamente

---

## 3. Verificação Pós-Migração

### 3.1. Confirmar Ausência de CFC 1

Execute as queries de verificação pós-migração de `docs/MIGRACAO_CFC_1_PARA_36.md`:

```sql
-- Verificar se ainda existem registros com cfc_id = 1
SELECT 'alunos' AS tabela, COUNT(*) AS total_com_cfc_1 FROM alunos WHERE cfc_id = 1
UNION ALL
SELECT 'turmas_teoricas' AS tabela, COUNT(*) AS total_com_cfc_1 FROM turmas_teoricas WHERE cfc_id = 1
UNION ALL
SELECT 'salas' AS tabela, COUNT(*) AS total_com_cfc_1 FROM salas WHERE cfc_id = 1
UNION ALL
SELECT 'instrutores' AS tabela, COUNT(*) AS total_com_cfc_1 FROM instrutores WHERE cfc_id = 1
UNION ALL
SELECT 'aulas' AS tabela, COUNT(*) AS total_com_cfc_1 FROM aulas WHERE cfc_id = 1
UNION ALL
SELECT 'veiculos' AS tabela, COUNT(*) AS total_com_cfc_1 FROM veiculos WHERE cfc_id = 1;
```

**Resultado Esperado:**
- ✅ Todas as linhas devem retornar `total_com_cfc_1 = 0`

---

## 4. Verificação de Logs

### 4.1. Logs da API de Turmas Teóricas

**Ação:**
1. Abra o modal "Matricular Alunos na Turma"
2. Verifique os logs do servidor: `logs/php_errors.log`

**Verificações nos Logs:**
- [ ] `[TURMAS TEORICAS API] CFC da Turma: 36` (ou o CFC real da turma)
- [ ] `[TURMAS TEORICAS API] Total candidatos brutos: >= 1`
- [ ] Se aluno 167 estiver nos candidatos: `[TURMAS TEORICAS API] ✅ ALUNO 167 ENCONTRADO NOS CANDIDATOS BRUTOS`
- [ ] Se aluno 167 não estiver: `[TURMAS TEORICAS API] 🔍 DIAGNÓSTICO ALUNO 167` com detalhes

**Resultado Esperado:**
- ✅ Logs mostram CFC correto (36)
- ✅ Logs mostram candidatos encontrados
- ✅ Se houver problema, logs de diagnóstico ajudam a identificar

---

## 5. Resumo de Validação

### ✅ Checklist Final

- [ ] **Diagnóstico:** Queries de diagnóstico executadas e revisadas
- [ ] **Migração:** UPDATEs executados manualmente (CFC 1 → 36)
- [ ] **Verificação:** Confirmado que não restam registros com `cfc_id = 1`
- [ ] **Histórico Aluno 167:** Exames OK, bloqueios liberados
- [ ] **Modal Turmas:** Aluno 167 aparece como elegível
- [ ] **Novo Aluno:** Criado com `cfc_id = 36` automaticamente
- [ ] **Novo Aluno Completo:** Aparece no modal de turmas após exames/financeiro OK
- [ ] **Edição Aluno:** CFC não pode ser alterado incorretamente
- [ ] **Logs:** Mostram CFC correto e candidatos encontrados

---

## 6. Problemas Conhecidos e Soluções

### Problema: Aluno 167 ainda não aparece no modal

**Soluções:**
1. Verificar logs do servidor para diagnóstico detalhado
2. Confirmar que turma 16 está com `cfc_id = 36`:
   ```sql
   SELECT id, nome, cfc_id FROM turmas_teoricas WHERE id = 16;
   ```
3. Confirmar que aluno 167 está com `cfc_id = 36`:
   ```sql
   SELECT id, nome, cfc_id, status FROM alunos WHERE id = 167;
   ```
4. Verificar exames do aluno 167:
   ```sql
   SELECT tipo, status, resultado FROM exames WHERE aluno_id = 167;
   ```
5. Verificar financeiro do aluno 167:
   ```sql
   SELECT COUNT(*) as faturas_vencidas 
   FROM financeiro_faturas 
   WHERE aluno_id = 167 AND status = 'vencida';
   ```

### Problema: Novos alunos sendo criados com CFC incorreto

**Soluções:**
1. Verificar se a lógica em `admin/api/alunos.php` está correta
2. Verificar logs da API ao criar aluno
3. Confirmar que `getCurrentUser()` retorna `cfc_id` correto

---

## 7. Notas Finais

- **CFC Canônico:** 36 (não mais 1)
- **CFC Legado:** 1 (deve ser migrado para 36)
- **Regra:** Todos os alunos e turmas do CFC Bom Conselho devem usar `cfc_id = 36`
- **Admin Global:** Pode gerenciar qualquer CFC, mas deve selecionar explicitamente ao criar alunos
- **Usuário CFC Específico:** Sempre usa o CFC da sessão automaticamente
- **Migração:** A migração CFC 1 → 36 é SEMPRE manual, via script documentado em `docs/MIGRACAO_CFC_1_PARA_36.md`
- **Nenhuma rotina automática** (cron, API, página web) deve disparar UPDATEs de CFC em massa

---

**Data de Criação:** 2025-11-21  
**Última Atualização:** 2025-11-21  
**Referência:** `docs/MIGRACAO_CFC_1_PARA_36.md`

