-- Migration 028: Adicionar campos de quantidade de aulas e minutos por aula em disciplinas
-- Melhora UX do cadastro mantendo minutos como valor canônico

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- Adicionar campos em theory_disciplines
ALTER TABLE `theory_disciplines`
ADD COLUMN `default_lessons_count` INT(11) DEFAULT NULL COMMENT 'Quantidade padrão de aulas',
ADD COLUMN `default_lesson_minutes` INT(11) DEFAULT NULL COMMENT 'Minutos por aula (hora-aula padrão, geralmente 50)',
ADD KEY `default_lessons_count` (`default_lessons_count`);

-- Adicionar campos opcionais em theory_course_disciplines (para registrar formato humano)
ALTER TABLE `theory_course_disciplines`
ADD COLUMN `lessons_count` INT(11) DEFAULT NULL COMMENT 'Quantidade de aulas para este curso',
ADD COLUMN `lesson_minutes` INT(11) DEFAULT NULL COMMENT 'Minutos por aula para este curso',
ADD KEY `lessons_count` (`lessons_count`);

SET FOREIGN_KEY_CHECKS = 1;
