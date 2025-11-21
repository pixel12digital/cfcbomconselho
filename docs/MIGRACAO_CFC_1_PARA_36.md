# Migração: CFC ID 1 → CFC ID 36

## ⚠️ IMPORTANTE

**Este script NÃO deve ser executado automaticamente pelo sistema.**

Execute manualmente via phpMyAdmin ou cliente SQL após revisão completa.

**Faça backup do banco de dados antes de executar qualquer UPDATE!**

---

## Contexto

- **CFC Canônico do CFC Bom Conselho:** ID 36
- **CFC ID 1:** Legado, não existe mais na tabela `cfcs` e é considerado lixo
- **Objetivo:** Migrar todos os registros que ainda usam `cfc_id = 1` para `cfc_id = 36`

---

## 1. Diagnóstico - Distribuição Atual de cfc_id

Execute estas queries para verificar a distribuição atual de `cfc_id` em cada tabela:

```sql
-- =====================================================
-- DIAGNÓSTICO: Distribuição de cfc_id por tabela
-- =====================================================

-- Alunos
SELECT cfc_id, COUNT(*) AS total, 
       SUM(CASE WHEN status = 'ativo' THEN 1 ELSE 0 END) AS ativos,
       SUM(CASE WHEN status != 'ativo' THEN 1 ELSE 0 END) AS inativos
FROM alunos
GROUP BY cfc_id
ORDER BY cfc_id;

-- Turmas Teóricas
SELECT cfc_id, COUNT(*) AS total
FROM turmas_teoricas
GROUP BY cfc_id
ORDER BY cfc_id;

-- Salas
SELECT cfc_id, COUNT(*) AS total,
       SUM(CASE WHEN ativa = 1 THEN 1 ELSE 0 END) AS ativas,
       SUM(CASE WHEN ativa = 0 THEN 1 ELSE 0 END) AS inativas
FROM salas
GROUP BY cfc_id
ORDER BY cfc_id;

-- Instrutores
SELECT cfc_id, COUNT(*) AS total,
       SUM(CASE WHEN ativo = 1 THEN 1 ELSE 0 END) AS ativos,
       SUM(CASE WHEN ativo = 0 THEN 1 ELSE 0 END) AS inativos
FROM instrutores
GROUP BY cfc_id
ORDER BY cfc_id;

-- Aulas
SELECT cfc_id, COUNT(*) AS total
FROM aulas
GROUP BY cfc_id
ORDER BY cfc_id;

-- Veículos
SELECT cfc_id, COUNT(*) AS total,
       SUM(CASE WHEN ativo = 1 THEN 1 ELSE 0 END) AS ativos,
       SUM(CASE WHEN ativo = 0 THEN 1 ELSE 0 END) AS inativos
FROM veiculos
GROUP BY cfc_id
ORDER BY cfc_id;

-- Verificar se CFC ID 1 existe na tabela cfcs
SELECT id, nome, cnpj, ativo
FROM cfcs
WHERE id = 1;

-- Verificar CFC ID 36 (canônico)
SELECT id, nome, cnpj, ativo
FROM cfcs
WHERE id = 36;
```

---

## 2. Migração Proposta

**⚠️ REVISE TODAS AS QUERIES ANTES DE EXECUTAR!**

```sql
-- =====================================================
-- MIGRAÇÃO: CFC ID 1 → CFC ID 36
-- Data: 2025-11-21
-- CFC Canônico: 36
-- =====================================================

-- IMPORTANTE: Verificar se CFC 36 existe antes de migrar
SELECT id, nome, cnpj FROM cfcs WHERE id = 36;
-- Se não existir, criar ou ajustar conforme necessário

-- =====================================================
-- 1. MIGRAR ALUNOS
-- =====================================================
-- Migrar alunos que ainda estão com cfc_id = 1
UPDATE alunos
SET cfc_id = 36
WHERE cfc_id = 1;

-- Verificar resultado
SELECT COUNT(*) as total_migrados
FROM alunos
WHERE cfc_id = 36;

-- =====================================================
-- 2. MIGRAR TURMAS TEÓRICAS
-- =====================================================
-- Migrar turmas teóricas que ainda estão com cfc_id = 1
UPDATE turmas_teoricas
SET cfc_id = 36
WHERE cfc_id = 1;

-- Verificar resultado
SELECT COUNT(*) as total_migrados
FROM turmas_teoricas
WHERE cfc_id = 36;

-- =====================================================
-- 3. MIGRAR SALAS
-- =====================================================
-- Migrar salas que ainda estão com cfc_id = 1
UPDATE salas
SET cfc_id = 36
WHERE cfc_id = 1;

-- Verificar resultado
SELECT COUNT(*) as total_migrados
FROM salas
WHERE cfc_id = 36;

-- =====================================================
-- 4. MIGRAR INSTRUTORES
-- =====================================================
-- Migrar instrutores que ainda estão com cfc_id = 1
UPDATE instrutores
SET cfc_id = 36
WHERE cfc_id = 1;

-- Verificar resultado
SELECT COUNT(*) as total_migrados
FROM instrutores
WHERE cfc_id = 36;

-- =====================================================
-- 5. MIGRAR AULAS
-- =====================================================
-- Migrar aulas que ainda estão com cfc_id = 1
UPDATE aulas
SET cfc_id = 36
WHERE cfc_id = 1;

-- Verificar resultado
SELECT COUNT(*) as total_migrados
FROM aulas
WHERE cfc_id = 36;

-- =====================================================
-- 6. MIGRAR VEÍCULOS
-- =====================================================
-- Migrar veículos que ainda estão com cfc_id = 1
UPDATE veiculos
SET cfc_id = 36
WHERE cfc_id = 1;

-- Verificar resultado
SELECT COUNT(*) as total_migrados
FROM veiculos
WHERE cfc_id = 36;
```

---

## 3. Verificação Pós-Migração

Execute estas queries para confirmar que não restou nenhum registro com `cfc_id = 1`:

```sql
-- =====================================================
-- VERIFICAÇÃO PÓS-MIGRAÇÃO
-- =====================================================

-- Verificar se ainda existem registros com cfc_id = 1
SELECT 'alunos' AS tabela, COUNT(*) AS total_com_cfc_1
FROM alunos
WHERE cfc_id = 1

UNION ALL

SELECT 'turmas_teoricas' AS tabela, COUNT(*) AS total_com_cfc_1
FROM turmas_teoricas
WHERE cfc_id = 1

UNION ALL

SELECT 'salas' AS tabela, COUNT(*) AS total_com_cfc_1
FROM salas
WHERE cfc_id = 1

UNION ALL

SELECT 'instrutores' AS tabela, COUNT(*) AS total_com_cfc_1
FROM instrutores
WHERE cfc_id = 1

UNION ALL

SELECT 'aulas' AS tabela, COUNT(*) AS total_com_cfc_1
FROM aulas
WHERE cfc_id = 1

UNION ALL

SELECT 'veiculos' AS tabela, COUNT(*) AS total_com_cfc_1
FROM veiculos
WHERE cfc_id = 1;

-- Resultado esperado: todas as linhas devem retornar 0

-- =====================================================
-- Verificar distribuição final (deve mostrar apenas 36)
-- =====================================================

-- Alunos
SELECT cfc_id, COUNT(*) AS total
FROM alunos
WHERE status = 'ativo'
GROUP BY cfc_id
ORDER BY cfc_id;

-- Turmas Teóricas
SELECT cfc_id, COUNT(*) AS total
FROM turmas_teoricas
GROUP BY cfc_id
ORDER BY cfc_id;

-- Salas
SELECT cfc_id, COUNT(*) AS total
FROM salas
WHERE ativa = 1
GROUP BY cfc_id
ORDER BY cfc_id;
```

---

## 4. Notas Importantes

1. **Backup obrigatório:** Faça backup completo do banco antes de executar
2. **Execução manual:** Execute cada UPDATE individualmente e verifique o resultado
3. **Transações:** Considere usar transações para poder fazer rollback se necessário:
   ```sql
   START TRANSACTION;
   -- Execute os UPDATEs aqui
   -- Se tudo estiver OK:
   COMMIT;
   -- Se houver problema:
   ROLLBACK;
   ```
4. **CFC 36 deve existir:** Certifique-se de que o CFC ID 36 existe na tabela `cfcs` antes de migrar
5. **Foreign Keys:** Se houver problemas com foreign keys, pode ser necessário desabilitá-las temporariamente:
   ```sql
   SET FOREIGN_KEY_CHECKS = 0;
   -- Execute os UPDATEs
   SET FOREIGN_KEY_CHECKS = 1;
   ```

---

## 5. Checklist de Execução

- [ ] Backup do banco de dados realizado
- [ ] Queries de diagnóstico executadas e resultados revisados
- [ ] CFC ID 36 confirmado como existente na tabela `cfcs`
- [ ] Queries de migração revisadas
- [ ] Migração executada (UPDATE por UPDATE)
- [ ] Queries de verificação pós-migração executadas
- [ ] Confirmado que não restam registros com `cfc_id = 1`
- [ ] Testes funcionais realizados (abrir turmas, alunos, etc.)

---

## 6. Rollback (se necessário)

Se precisar reverter a migração (não recomendado, apenas em caso de erro):

```sql
-- ⚠️ APENAS EM CASO DE ERRO - REVERTER PARA CFC 1
-- ⚠️ NÃO RECOMENDADO - CFC 1 é legado

UPDATE alunos SET cfc_id = 1 WHERE cfc_id = 36;
UPDATE turmas_teoricas SET cfc_id = 1 WHERE cfc_id = 36;
UPDATE salas SET cfc_id = 1 WHERE cfc_id = 36;
UPDATE instrutores SET cfc_id = 1 WHERE cfc_id = 36;
UPDATE aulas SET cfc_id = 1 WHERE cfc_id = 36;
UPDATE veiculos SET cfc_id = 1 WHERE cfc_id = 36;
```

**⚠️ NÃO execute o rollback a menos que seja absolutamente necessário!**

