-- =====================================================
-- CORREÇÃO PARA HOMOLOG - Aluno 167 + Turma 19
-- =====================================================
-- 
-- ⚠️ EXECUTAR APENAS EM HOMOLOG/TESTE
-- ⚠️ NÃO executar em produção sem validação
-- 
-- Problemas encontrados pelo diagnóstico:
-- 1. Status do aluno = 'concluido' (deveria ser 'ativo')
-- 2. CFC do aluno = 36, mas turma é CFC 1
-- 
-- Data: 12/12/2025
-- Diagnóstico: admin/tools/diagnostico-alunos-aptos-api.php
-- 
-- =====================================================

-- =====================================================
-- 1. CORREÇÃO: Status do Aluno
-- =====================================================

-- Atualizar status do aluno 167 para 'ativo'
UPDATE alunos 
SET status = 'ativo' 
WHERE id = 167;

-- Verificar se funcionou
SELECT 
    id, 
    nome, 
    status, 
    cfc_id,
    CASE 
        WHEN status IN ('ativo', 'em_andamento') THEN '✅ Status OK'
        ELSE CONCAT('❌ Status NÃO permitido: ', status)
    END as verificacao_status
FROM alunos 
WHERE id = 167;
-- Esperado: status = 'ativo', verificacao_status = '✅ Status OK'

-- =====================================================
-- 2. CORREÇÃO: CFC do Aluno
-- =====================================================
-- 
-- ⚠️ ATENÇÃO: Isso muda o CFC do aluno 167 para o CFC da turma 19
-- 
-- Se o CFC 36 for o correto do aluno, considere:
-- - Opção A: Criar uma turma teórica no CFC 36 para testes
-- - Opção B: Usar uma turma existente do CFC 36
-- 
-- Se o CFC 1 for realmente o correto, execute a query abaixo:
-- 

-- Atualizar CFC do aluno para o CFC da turma 19
UPDATE alunos 
SET cfc_id = (SELECT cfc_id FROM turmas_teoricas WHERE id = 19)
WHERE id = 167;

-- Verificar se funcionou
SELECT 
    a.id, 
    a.nome, 
    a.status, 
    a.cfc_id as aluno_cfc_id,
    (SELECT cfc_id FROM turmas_teoricas WHERE id = 19) as turma_cfc_id,
    CASE 
        WHEN a.cfc_id = (SELECT cfc_id FROM turmas_teoricas WHERE id = 19) 
        THEN '✅ CFC Compatível' 
        ELSE CONCAT('❌ CFC diferente: aluno=', a.cfc_id, ', turma=', (SELECT cfc_id FROM turmas_teoricas WHERE id = 19))
    END as verificacao_cfc
FROM alunos a
WHERE id = 167;
-- Esperado: aluno_cfc_id = turma_cfc_id, verificacao_cfc = '✅ CFC Compatível'

-- =====================================================
-- 3. VALIDAÇÃO FINAL: Query Base da API
-- =====================================================
-- 
-- Esta query simula a query base da API (antes dos filtros de exames/financeiro)
-- Se retornar 1 linha, o aluno passou na validação base ✅
-- Se retornar 0 linhas, ainda há problema ❌
-- 

SELECT 
    a.id, 
    a.nome, 
    a.status, 
    a.cfc_id,
    '✅ Aluno passou na query base' as resultado
FROM alunos a
WHERE a.id = 167
  AND a.status IN ('ativo', 'em_andamento')
  AND a.cfc_id = (SELECT cfc_id FROM turmas_teoricas WHERE id = 19);

-- =====================================================
-- 4. RESUMO FINAL
-- =====================================================

SELECT 
    'RESUMO FINAL' as tipo,
    a.id as aluno_id,
    a.nome as aluno_nome,
    a.status as status_atual,
    a.cfc_id as cfc_atual,
    (SELECT cfc_id FROM turmas_teoricas WHERE id = 19) as cfc_turma_19,
    CASE 
        WHEN a.status IN ('ativo', 'em_andamento') THEN '✅'
        ELSE '❌'
    END as status_ok,
    CASE 
        WHEN a.cfc_id = (SELECT cfc_id FROM turmas_teoricas WHERE id = 19) THEN '✅'
        ELSE '❌'
    END as cfc_ok,
    CASE 
        WHEN a.status IN ('ativo', 'em_andamento') 
            AND a.cfc_id = (SELECT cfc_id FROM turmas_teoricas WHERE id = 19)
        THEN '✅ PRONTO PARA APARECER NA LISTA'
        ELSE '❌ AINDA HÁ PROBLEMAS'
    END as resultado_final
FROM alunos a
WHERE id = 167;

