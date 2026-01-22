# Instruções para Executar a Migração CFC 1 → 36

## ⚠️ IMPORTANTE

**Antes de executar:**
1. ✅ Faça backup completo do banco de dados
2. ✅ Revise as queries em `docs/MIGRACAO_CFC_1_PARA_36.md`
3. ✅ Confirme que o CFC ID 36 existe na tabela `cfcs`

---

## Como Executar

### Opção 1: Via Script PHP (Recomendado)

1. Acesse via navegador:
   ```
   admin/tools/executar-migracao-cfc-36.php
   ```

2. O script mostrará:
   - Diagnóstico inicial (distribuição de cfc_id por tabela)
   - Confirmação de que CFC 36 existe
   - Total de registros para migrar

3. Clique em **"Confirmar e Executar Migração"**

4. O script executará:
   - Migração de todas as tabelas (alunos, turmas_teoricas, salas, instrutores, aulas, veiculos)
   - Verificação pós-migração
   - Resumo final com estatísticas

### Opção 2: Via phpMyAdmin (Manual)

1. Abra o arquivo `docs/MIGRACAO_CFC_1_PARA_36.md`

2. Execute as queries na seguinte ordem:
   - Primeiro: Queries de diagnóstico (seção 1)
   - Depois: Queries de migração (seção 2)
   - Por fim: Queries de verificação (seção 3)

3. Revise os resultados de cada query antes de prosseguir

---

## O que o Script Faz

### 1. Diagnóstico Inicial
- Lista distribuição de `cfc_id` em cada tabela
- Verifica se CFC 36 existe
- Conta quantos registros precisam ser migrados

### 2. Execução da Migração
- Executa `UPDATE` em cada tabela:
  - `alunos`
  - `turmas_teoricas`
  - `salas`
  - `instrutores`
  - `aulas`
  - `veiculos`
- Mostra quantos registros foram migrados em cada tabela

### 3. Verificação Pós-Migração
- Verifica se ainda existem registros com `cfc_id = 1`
- Mostra distribuição final por CFC
- Gera resumo com estatísticas

---

## Resultado Esperado

Após a migração bem-sucedida:

✅ **Todas as tabelas devem mostrar:**
- Nenhum registro com `cfc_id = 1`
- Todos os registros com `cfc_id = 36` (ou outro CFC válido)

✅ **Distribuição final:**
- Apenas `cfc_id = 36` (ou outros CFCs válidos, se houver)
- Nenhum `cfc_id = 1`

---

## Próximos Passos Após Migração

1. Execute o checklist de testes: `docs/CHECKLIST_TESTES_CFC_36.md`

2. Verifique:
   - Histórico do aluno 167: `admin/index.php?page=historico-aluno&id=167`
   - Modal de turmas teóricas: `admin/index.php?page=turmas-teoricas&acao=detalhes&turma_id=16`
   - Ferramentas de diagnóstico:
     - `admin/tools/diagnostico-cfc-turma-16.php`
     - `admin/tools/diagnostico-cfc-alunos.php?cfc_canonico=36`

---

## Em Caso de Erro

Se o script encontrar erros:

1. **CFC 36 não existe:**
   - Crie o CFC 36 na tabela `cfcs` primeiro
   - Ou ajuste o ID do CFC canônico no script

2. **Erro de foreign key:**
   - Verifique se há constraints que impedem a migração
   - Pode ser necessário desabilitar temporariamente: `SET FOREIGN_KEY_CHECKS = 0;`

3. **Erro de permissão:**
   - Verifique se o usuário do banco tem permissão para UPDATE

---

## Segurança

- ✅ O script só executa após confirmação explícita (`?confirmar=1`)
- ✅ Mostra diagnóstico completo antes de executar
- ✅ Exibe resultados detalhados de cada operação
- ✅ Não executa automaticamente em nenhum momento

---

**Data:** 2025-11-21  
**Script:** `admin/tools/executar-migracao-cfc-36.php`  
**Documentação:** `docs/MIGRACAO_CFC_1_PARA_36.md`

