-- Script SQL de Rollback: Remover lessons de teste criadas pelo script test_cluster_stack_lessons.sql
-- Remove apenas registros com notes='TEST_STACK_1400'

-- ============================================
-- CONFIGURAÇÃO
-- ============================================
SET @test_marker = 'TEST_STACK_1400';
SET @target_date = '2026-01-15';
SET @target_time = '14:00:00';

-- ============================================
-- RELATÓRIO ANTES DE DELETAR
-- ============================================
SELECT 
    COUNT(*) as total_lessons_para_deletar,
    GROUP_CONCAT(id ORDER BY id SEPARATOR ', ') as lesson_ids,
    scheduled_date,
    scheduled_time,
    GROUP_CONCAT(CONCAT('Instrutor ', instructor_id, ' - Aluno ', student_id) SEPARATOR '; ') as detalhes
FROM lessons 
WHERE notes = @test_marker 
  AND scheduled_date = @target_date 
  AND scheduled_time = @target_time
GROUP BY scheduled_date, scheduled_time;

-- ============================================
-- DELETAR LESSONS DE TESTE
-- ============================================
DELETE FROM lessons 
WHERE notes = @test_marker 
  AND scheduled_date = @target_date 
  AND scheduled_time = @target_time;

-- ============================================
-- VALIDAÇÃO PÓS-DELETE
-- ============================================
SELECT 
    CASE 
        WHEN COUNT(*) = 0 THEN '✅ Todos os registros de teste foram removidos com sucesso!'
        ELSE CONCAT('⚠️ ATENÇÃO: Ainda existem ', COUNT(*), ' registro(s) de teste. Verificar manualmente.')
    END as resultado,
    COUNT(*) as registros_restantes
FROM lessons 
WHERE notes = @test_marker 
  AND scheduled_date = @target_date 
  AND scheduled_time = @target_time;
