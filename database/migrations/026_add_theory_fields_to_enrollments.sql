-- Migration 026: Adicionar campos de curso teórico em enrollments
-- Permite vincular matrícula a template de curso ou turma teórica

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- Adicionar campos opcionais em enrollments
ALTER TABLE `enrollments`
ADD COLUMN `theory_course_id` int(11) DEFAULT NULL COMMENT 'Template de curso teórico (opcional)',
ADD COLUMN `theory_class_id` int(11) DEFAULT NULL COMMENT 'Turma teórica (opcional)',
ADD KEY `theory_course_id` (`theory_course_id`),
ADD KEY `theory_class_id` (`theory_class_id`),
ADD CONSTRAINT `enrollments_ibfk_theory_course` FOREIGN KEY (`theory_course_id`) REFERENCES `theory_courses` (`id`) ON DELETE SET NULL,
ADD CONSTRAINT `enrollments_ibfk_theory_class` FOREIGN KEY (`theory_class_id`) REFERENCES `theory_classes` (`id`) ON DELETE SET NULL;

SET FOREIGN_KEY_CHECKS = 1;
