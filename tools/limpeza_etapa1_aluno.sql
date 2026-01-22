-- ============================================
-- ETAPA 1: Limpeza de Aluno e Dependências
-- ============================================
-- IMPORTANTE: Executa dentro de transação com validações
-- ============================================

START TRANSACTION;

-- 1) Pegar o ID do único aluno (ou ajuste o WHERE se preferir por CPF/email/nome)
SET @STUDENT_ID := (SELECT id FROM students ORDER BY id ASC LIMIT 1);

-- Segurança: conferir antes de apagar
SELECT @STUDENT_ID AS student_id_encontrado, (SELECT COUNT(*) FROM students) AS total_students;

-- Verificar contagens ANTES da limpeza
SELECT 'ANTES DA LIMPEZA' AS etapa;
SELECT 
    'students' AS tabela, COUNT(*) AS registros FROM students
UNION ALL
SELECT 'enrollments', COUNT(*) FROM enrollments WHERE student_id = @STUDENT_ID
UNION ALL
SELECT 'lessons', COUNT(*) FROM lessons WHERE student_id = @STUDENT_ID
UNION ALL
SELECT 'student_history', COUNT(*) FROM student_history WHERE student_id = @STUDENT_ID
UNION ALL
SELECT 'student_steps', COUNT(*) FROM student_steps ss JOIN enrollments e ON e.id = ss.enrollment_id WHERE e.student_id = @STUDENT_ID
UNION ALL
SELECT 'theory_attendance', COUNT(*) FROM theory_attendance WHERE student_id = @STUDENT_ID
UNION ALL
SELECT 'theory_enrollments', COUNT(*) FROM theory_enrollments WHERE student_id = @STUDENT_ID
UNION ALL
SELECT 'reschedule_requests', COUNT(*) FROM reschedule_requests WHERE student_id = @STUDENT_ID
UNION ALL
SELECT 'user_recent_financial_queries', COUNT(*) FROM user_recent_financial_queries WHERE student_id = @STUDENT_ID;

-- 2) Apagar dependências (filhas) do aluno
DELETE FROM theory_attendance WHERE student_id = @STUDENT_ID;
DELETE FROM theory_enrollments WHERE student_id = @STUDENT_ID;
DELETE FROM user_recent_financial_queries WHERE student_id = @STUDENT_ID;
DELETE FROM student_history WHERE student_id = @STUDENT_ID;

-- lessons dependem de student_id e podem ter theory_sessions e reschedule_requests ligados
DELETE FROM reschedule_requests WHERE student_id = @STUDENT_ID;

-- 3) Apagar aulas/agendamentos do aluno
DELETE FROM lessons WHERE student_id = @STUDENT_ID;

-- 4) Limpar sessões teóricas que ficaram órfãs (se houver)
-- Se o FK for ON DELETE CASCADE, isso já cai automaticamente; se não for, removemos órfãs:
DELETE ts
FROM theory_sessions ts
LEFT JOIN lessons l ON l.id = ts.lesson_id
WHERE ts.lesson_id IS NOT NULL AND l.id IS NULL;

-- 5) Apagar matrículas do aluno (e steps ligados às matrículas)
-- student_steps referencia enrollments (não students direto)
DELETE ss
FROM student_steps ss
JOIN enrollments e ON e.id = ss.enrollment_id
WHERE e.student_id = @STUDENT_ID;

DELETE FROM enrollments WHERE student_id = @STUDENT_ID;

-- 6) Finalmente, apagar o aluno
DELETE FROM students WHERE id = @STUDENT_ID;

-- Verificar contagens DEPOIS da limpeza (antes do COMMIT)
SELECT 'DEPOIS DA LIMPEZA (ANTES DO COMMIT)' AS etapa;
SELECT 
    'students' AS tabela, COUNT(*) AS registros FROM students
UNION ALL
SELECT 'enrollments', COUNT(*) FROM enrollments
UNION ALL
SELECT 'lessons', COUNT(*) FROM lessons
UNION ALL
SELECT 'student_history', COUNT(*) FROM student_history
UNION ALL
SELECT 'student_steps', COUNT(*) FROM student_steps
UNION ALL
SELECT 'theory_attendance', COUNT(*) FROM theory_attendance
UNION ALL
SELECT 'theory_enrollments', COUNT(*) FROM theory_enrollments
UNION ALL
SELECT 'reschedule_requests', COUNT(*) FROM reschedule_requests
UNION ALL
SELECT 'user_recent_financial_queries', COUNT(*) FROM user_recent_financial_queries;

-- Validação final: confirmar que students = 0
SET @STUDENTS_COUNT := (SELECT COUNT(*) FROM students);
SELECT @STUDENTS_COUNT AS students_restantes;

-- Se students = 0, pode fazer COMMIT; senão, fazer ROLLBACK
-- (O COMMIT será feito manualmente após validação visual)

-- COMMIT;  -- Descomente após validar os resultados acima
