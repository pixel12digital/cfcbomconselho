-- Script SQL Idempotente: Criar 5 lessons práticas no mesmo horário para testar agrupamento/colapso
-- Data: 15/01/2026 14:00–14:50
-- Marca: notes='TEST_STACK_1400'

-- ============================================
-- CONFIGURAÇÃO
-- ============================================
SET @target_date = '2026-01-15';
SET @target_time = '14:00:00';
SET @target_duration = 50; -- minutos
SET @test_marker = 'TEST_STACK_1400';
SET @cfc_id = 1; -- Ajustar se necessário

-- ============================================
-- BUSCAR RECURSOS DISPONÍVEIS (com fallback)
-- ============================================

-- Buscar cfc_id (se não especificado, usar 1)
SET @cfc_id = COALESCE((SELECT MIN(id) FROM cfcs LIMIT 1), @cfc_id);

-- Buscar instrutores disponíveis (mínimo 1, pode repetir)
SET @instructor_1 = (SELECT id FROM instructors WHERE cfc_id = @cfc_id AND is_active = 1 LIMIT 1);
SET @instructor_2 = (SELECT id FROM instructors WHERE cfc_id = @cfc_id AND is_active = 1 ORDER BY id LIMIT 1 OFFSET 1);
SET @instructor_3 = (SELECT id FROM instructors WHERE cfc_id = @cfc_id AND is_active = 1 ORDER BY id LIMIT 1 OFFSET 2);
SET @instructor_4 = COALESCE((SELECT id FROM instructors WHERE cfc_id = @cfc_id AND is_active = 1 ORDER BY id LIMIT 1 OFFSET 3), @instructor_1);
SET @instructor_5 = COALESCE((SELECT id FROM instructors WHERE cfc_id = @cfc_id AND is_active = 1 ORDER BY id LIMIT 1 OFFSET 4), @instructor_1);

-- Validar recursos (será verificado antes do INSERT)

-- Buscar alunos e enrollments disponíveis (mínimo 1, pode repetir)
SET @student_1 = (SELECT id FROM students WHERE cfc_id = @cfc_id LIMIT 1);
SET @student_2 = (SELECT id FROM students WHERE cfc_id = @cfc_id ORDER BY id LIMIT 1 OFFSET 1);
SET @student_3 = (SELECT id FROM students WHERE cfc_id = @cfc_id ORDER BY id LIMIT 1 OFFSET 2);
SET @student_4 = COALESCE((SELECT id FROM students WHERE cfc_id = @cfc_id ORDER BY id LIMIT 1 OFFSET 3), @student_1);
SET @student_5 = COALESCE((SELECT id FROM students WHERE cfc_id = @cfc_id ORDER BY id LIMIT 1 OFFSET 4), @student_1);

SET @enrollment_1 = (SELECT id FROM enrollments WHERE student_id = @student_1 AND cfc_id = @cfc_id ORDER BY created_at DESC LIMIT 1);
SET @enrollment_2 = (SELECT id FROM enrollments WHERE student_id = @student_2 AND cfc_id = @cfc_id ORDER BY created_at DESC LIMIT 1);
SET @enrollment_3 = (SELECT id FROM enrollments WHERE student_id = @student_3 AND cfc_id = @cfc_id ORDER BY created_at DESC LIMIT 1);
SET @enrollment_4 = COALESCE((SELECT id FROM enrollments WHERE student_id = @student_4 AND cfc_id = @cfc_id ORDER BY created_at DESC LIMIT 1), @enrollment_1);
SET @enrollment_5 = COALESCE((SELECT id FROM enrollments WHERE student_id = @student_5 AND cfc_id = @cfc_id ORDER BY created_at DESC LIMIT 1), @enrollment_1);

-- Buscar veículos disponíveis (mínimo 1, pode repetir)
SET @vehicle_1 = (SELECT id FROM vehicles WHERE cfc_id = @cfc_id AND is_active = 1 LIMIT 1);
SET @vehicle_2 = (SELECT id FROM vehicles WHERE cfc_id = @cfc_id AND is_active = 1 ORDER BY id LIMIT 1 OFFSET 1);
SET @vehicle_3 = COALESCE((SELECT id FROM vehicles WHERE cfc_id = @cfc_id AND is_active = 1 ORDER BY id LIMIT 1 OFFSET 2), @vehicle_1);
SET @vehicle_4 = COALESCE((SELECT id FROM vehicles WHERE cfc_id = @cfc_id AND is_active = 1 ORDER BY id LIMIT 1 OFFSET 3), @vehicle_1);
SET @vehicle_5 = COALESCE((SELECT id FROM vehicles WHERE cfc_id = @cfc_id AND is_active = 1 ORDER BY id LIMIT 1 OFFSET 4), @vehicle_1);

-- Se não houver enrollments, usar 0 (aceitável para testes)
-- NOTA: Se @instructor_1, @vehicle_1 ou @student_1 forem NULL, o INSERT falhará.
-- Certifique-se de ter pelo menos 1 instrutor ativo, 1 aluno e 1 veículo antes de executar.
SET @enrollment_1 = COALESCE(@enrollment_1, 0);
SET @enrollment_2 = COALESCE(@enrollment_2, 0);
SET @enrollment_3 = COALESCE(@enrollment_3, 0);
SET @enrollment_4 = COALESCE(@enrollment_4, 0);
SET @enrollment_5 = COALESCE(@enrollment_5, 0);

-- ============================================
-- REMOVER LESSONS DE TESTE EXISTENTES (idempotência)
-- ============================================
DELETE FROM lessons 
WHERE notes = @test_marker 
  AND scheduled_date = @target_date 
  AND scheduled_time = @target_time;

-- ============================================
-- INSERIR 5 LESSONS PRÁTICAS (mesmo horário)
-- ============================================

INSERT INTO lessons (
    cfc_id,
    student_id,
    enrollment_id,
    instructor_id,
    vehicle_id,
    type,
    status,
    scheduled_date,
    scheduled_time,
    duration_minutes,
    notes,
    created_at
) VALUES
-- Lesson 1
(@cfc_id, @student_1, @enrollment_1, @instructor_1, @vehicle_1, 'pratica', 'agendada', @target_date, @target_time, @target_duration, @test_marker, NOW()),
-- Lesson 2
(@cfc_id, @student_2, @enrollment_2, @instructor_2, @vehicle_2, 'pratica', 'agendada', @target_date, @target_time, @target_duration, @test_marker, NOW()),
-- Lesson 3
(@cfc_id, @student_3, @enrollment_3, @instructor_3, @vehicle_3, 'pratica', 'agendada', @target_date, @target_time, @target_duration, @test_marker, NOW()),
-- Lesson 4
(@cfc_id, @student_4, @enrollment_4, @instructor_4, @vehicle_4, 'pratica', 'agendada', @target_date, @target_time, @target_duration, @test_marker, NOW()),
-- Lesson 5
(@cfc_id, @student_5, @enrollment_5, @instructor_5, @vehicle_5, 'pratica', 'agendada', @target_date, @target_time, @target_duration, @test_marker, NOW());

-- ============================================
-- VALIDAÇÃO E RELATÓRIO
-- ============================================
SELECT 
    '✅ Script executado com sucesso!' as status,
    COUNT(*) as total_lessons_criadas,
    GROUP_CONCAT(id ORDER BY id SEPARATOR ', ') as lesson_ids,
    @target_date as data_teste,
    @target_time as horario_teste,
    @test_marker as marcador
FROM lessons 
WHERE notes = @test_marker 
  AND scheduled_date = @target_date 
  AND scheduled_time = @target_time;
