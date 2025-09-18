-- Verificação rápida da exclusão dos alunos de teste
-- Execute este SQL no phpMyAdmin ou MySQL

-- 1. Verificar se os alunos ainda existem
SELECT 'VERIFICAÇÃO - Alunos de teste:' as status;
SELECT id, nome, cpf FROM alunos WHERE id IN (113, 127, 128);

-- 2. Verificar aulas órfãs
SELECT 'VERIFICAÇÃO - Aulas órfãs:' as status;
SELECT COUNT(*) as total_aulas_orfas FROM aulas WHERE aluno_id IN (113, 127, 128);

-- 3. Verificar slots órfãos
SELECT 'VERIFICAÇÃO - Slots órfãos:' as status;
SELECT COUNT(*) as total_slots_orfos FROM aulas_slots WHERE aluno_id IN (113, 127, 128);

-- 4. Verificar logs órfãos
SELECT 'VERIFICAÇÃO - Logs órfãos:' as status;
SELECT COUNT(*) as total_logs_orfos FROM logs WHERE registro_id IN (113, 127, 128) AND tabela = 'alunos';

-- 5. Resultado final
SELECT 
    CASE 
        WHEN (SELECT COUNT(*) FROM alunos WHERE id IN (113, 127, 128)) = 0 
             AND (SELECT COUNT(*) FROM aulas WHERE aluno_id IN (113, 127, 128)) = 0
             AND (SELECT COUNT(*) FROM aulas_slots WHERE aluno_id IN (113, 127, 128)) = 0
             AND (SELECT COUNT(*) FROM logs WHERE registro_id IN (113, 127, 128) AND tabela = 'alunos') = 0
        THEN '✅ EXCLUSÃO COMPLETA E BEM-SUCEDIDA!'
        ELSE '❌ EXCLUSÃO INCOMPLETA - DADOS ÓRFÃOS ENCONTRADOS'
    END as resultado_final;
